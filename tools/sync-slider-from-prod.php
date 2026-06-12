<?php
/**
 * Sync shop_slider rows and image files from production to local dev.
 *
 * Usage: php tools/sync-slider-from-prod.php
 */

$root = dirname(__DIR__);
$prod_cfg = include $root . '/wa-config/db.php.remote';
$local_cfg = include $root . '/wa-config/db.php';

function db_connect(array $cfg)
{
    $c = $cfg['default'];
    $mysqli = new mysqli($c['host'], $c['user'], $c['password'], $c['database']);
    if ($mysqli->connect_error) {
        throw new RuntimeException('DB connect failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}

function esc($mysqli, $value)
{
    return $mysqli->real_escape_string((string) $value);
}

$prod = db_connect($prod_cfg);
$local = db_connect($local_cfg);

$img_dir = $root . '/wa-data/public/shop/slider/img';
if (!is_dir($img_dir)) {
    throw new RuntimeException('Image dir missing: ' . $img_dir);
}

$base_url = 'https://almamed.su/wa-data/public/shop/slider/img/';
$downloaded = 0;
$skipped = 0;
$failed = 0;

$collect_files = function (array $row) {
    $files = array();
    foreach (array('img', 'img_tablet', 'img_mobile') as $key) {
        if (empty($row[$key])) {
            continue;
        }
        $path = $row[$key];
        $files[basename($path)] = $path;
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', basename($path));
        $files[$webp] = dirname($path) . '/' . $webp;
    }

    return $files;
};

$all_files = array();
$prod_rows = array();
$result = $prod->query('SELECT * FROM shop_slider ORDER BY id');
while ($row = $result->fetch_assoc()) {
    $prod_rows[] = $row;
    $all_files = array_merge($all_files, $collect_files($row));
}
$all_files = array_unique($all_files);

echo 'Slides on prod: ' . count($prod_rows) . PHP_EOL;
echo 'Files to sync: ' . count($all_files) . PHP_EOL;

foreach ($all_files as $basename => $public_path) {
    $target = $img_dir . '/' . $basename;
    $url = $base_url . rawurlencode($basename);
    // rawurlencode breaks spaces as %20 which is correct

    if (file_exists($target) && filesize($target) > 0) {
        $skipped++;
        continue;
    }

    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 30,
            'user_agent' => 'AlmamedLocalSync/1.0',
        ),
    ));

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 100) {
        // retry with literal path for names containing spaces/parentheses
        $url = 'https://almamed.su' . $public_path;
        $data = @file_get_contents($url, false, $ctx);
    }

    if ($data === false || strlen($data) < 100) {
        echo "FAIL: {$basename}\n";
        $failed++;
        continue;
    }

    if (file_put_contents($target, $data) === false) {
        echo "WRITE FAIL: {$basename}\n";
        $failed++;
        continue;
    }

    echo "OK: {$basename}\n";
    $downloaded++;
}

$columns = array(
    'sort', 'link', 'img', 'img_tablet', 'img_mobile', 'alt', 'enabled',
    'date_from', 'date_to', 'sales_manager', 'sales_manager_id',
    'content_manager', 'content_manager_id', 'views_count', 'clicks_count',
);

$updated = 0;
$inserted = 0;

foreach ($prod_rows as $row) {
    $id = (int) $row['id'];
    $exists = $local->query("SELECT id FROM shop_slider WHERE id = {$id}")->fetch_assoc();

    $sets = array();
    foreach ($columns as $col) {
        if (!array_key_exists($col, $row)) {
            continue;
        }
        $val = $row[$col];
        if ($val === null) {
            if (in_array($col, array('date_from', 'date_to', 'sales_manager_id', 'content_manager_id'), true)) {
                $sets[] = "`{$col}` = NULL";
            } else {
                $sets[] = "`{$col}` = ''";
            }
        } elseif ($val === '' && in_array($col, array('date_from', 'date_to', 'sales_manager_id', 'content_manager_id'), true)) {
            $sets[] = "`{$col}` = NULL";
        } elseif (is_numeric($val) && !in_array($col, array('sales_manager', 'content_manager', 'link', 'img', 'img_tablet', 'img_mobile', 'alt'), true)) {
            $sets[] = "`{$col}` = " . (int) $val;
        } else {
            $sets[] = "`{$col}` = '" . esc($local, $val) . "'";
        }
    }

    if ($exists) {
        $sql = 'UPDATE shop_slider SET ' . implode(', ', $sets) . " WHERE id = {$id}";
        $local->query($sql);
        $updated++;
    } else {
        $cols = array_merge(array('id'), $columns);
        $vals = array((string) $id);
        foreach ($columns as $col) {
            $val = ifset($row, $col);
            if ($val === null) {
                if (in_array($col, array('date_from', 'date_to', 'sales_manager_id', 'content_manager_id'), true)) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = "''";
                }
            } elseif ($val === '' && in_array($col, array('date_from', 'date_to', 'sales_manager_id', 'content_manager_id'), true)) {
                $vals[] = 'NULL';
            } elseif (is_numeric($val) && !in_array($col, array('sales_manager', 'content_manager', 'link', 'img', 'img_tablet', 'img_mobile', 'alt'), true)) {
                $vals[] = (string) (int) $val;
            } else {
                $vals[] = "'" . esc($local, $val) . "'";
            }
        }
        $sql = 'INSERT INTO shop_slider (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $vals) . ')';
        $local->query($sql);
        $inserted++;
    }
}

// Disable local-only rows that no longer exist on prod (e.g. duplicate sort conflicts)
$prod_ids = array_map(function ($r) { return (int) $r['id']; }, $prod_rows);
$local_result = $local->query('SELECT id FROM shop_slider');
while ($local_row = $local_result->fetch_assoc()) {
    $lid = (int) $local_row['id'];
    if (!in_array($lid, $prod_ids, true)) {
        $local->query("UPDATE shop_slider SET enabled = 0 WHERE id = {$lid}");
        echo "Disabled orphan local slide {$lid}\n";
    }
}

echo PHP_EOL;
echo "Downloaded: {$downloaded}, skipped: {$skipped}, failed: {$failed}\n";
echo "DB updated: {$updated}, inserted: {$inserted}\n";

$enabled = $local->query("SELECT id, sort, enabled, img FROM shop_slider WHERE enabled = 1 ORDER BY sort, id");
echo PHP_EOL . "Enabled slides locally:\n";
while ($r = $enabled->fetch_assoc()) {
    echo "  #{$r['id']} sort={$r['sort']} {$r['img']}\n";
}

function ifset($arr, $key, $default = null)
{
    return isset($arr[$key]) ? $arr[$key] : $default;
}
