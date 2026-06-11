#!/usr/bin/env php
<?php
/**
 * One-off generator for tablet (tb_*) and mobile (sm_*) slider banners.
 * Usage: php scripts/generate-slider-banners.php [--force]
 */

$root = dirname(__DIR__);
$db = require $root . '/wa-config/db.php';

$force = in_array('--force', $argv, true);
$db_config = $db['default'];

$mysqli = new mysqli(
    $db_config['host'],
    $db_config['user'],
    $db_config['password'],
    $db_config['database']
);

if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connection failed: {$mysqli->connect_error}\n");
    exit(1);
}

$mysqli->set_charset('utf8');

ensure_columns($mysqli);

const TABLET_WIDTH = 992;
const MOBILE_WIDTH = 576;

$img_dir = $root . '/wa-data/public/shop/slider/img';
$stats = array('processed' => 0, 'updated' => 0, 'skipped' => 0);

$result = $mysqli->query('SELECT id, img, img_tablet, img_mobile FROM shop_slider ORDER BY sort ASC');
if (!$result) {
    fwrite(STDERR, "Query failed: {$mysqli->error}\n");
    exit(1);
}

while ($slide = $result->fetch_assoc()) {
    $desktop = trim((string) $slide['img']);
    if ($desktop === '') {
        continue;
    }

    $stats['processed']++;
    $basename = basename($desktop);
    $desktop_path = $img_dir . '/' . $basename;

    if (!is_file($desktop_path)) {
        fwrite(STDERR, "Missing desktop file for slide {$slide['id']}: {$desktop_path}\n");
        continue;
    }

    $tablet_public = '/wa-data/public/shop/slider/img/tb_' . $basename;
    $mobile_public = '/wa-data/public/shop/slider/img/sm_' . $basename;
    $tablet_path = $img_dir . '/tb_' . $basename;
    $mobile_path = $img_dir . '/sm_' . $basename;

    $has_tablet = is_file($tablet_path);
    $has_mobile = is_file($mobile_path);

    if (!$force && trim((string) $slide['img_tablet']) !== '' && $has_tablet
        && trim((string) $slide['img_mobile']) !== '' && $has_mobile
    ) {
        $stats['skipped']++;
        continue;
    }

    if ($force || !$has_tablet) {
        resize_image($desktop_path, $tablet_path, TABLET_WIDTH);
    }
    if ($force || !$has_mobile) {
        resize_image($desktop_path, $mobile_path, MOBILE_WIDTH);
    }

    $stmt = $mysqli->prepare('UPDATE shop_slider SET img_tablet = ?, img_mobile = ? WHERE id = ?');
    $stmt->bind_param('ssi', $tablet_public, $mobile_public, $slide['id']);
    $stmt->execute();
    $stmt->close();

    $stats['updated']++;
    echo "Slide {$slide['id']}: {$basename}\n";
}

echo "\nProcessed: {$stats['processed']}\n";
echo "Updated: {$stats['updated']}\n";
echo "Skipped: {$stats['skipped']}\n";

function ensure_columns(mysqli $mysqli)
{
    $columns = array(
        'alt' => "ALTER TABLE `shop_slider` ADD `alt` varchar(512) NOT NULL DEFAULT ''",
        'img_tablet' => "ALTER TABLE `shop_slider` ADD `img_tablet` varchar(512) NOT NULL DEFAULT ''",
        'img_mobile' => "ALTER TABLE `shop_slider` ADD `img_mobile` varchar(512) NOT NULL DEFAULT ''",
    );

    foreach ($columns as $sql) {
        try {
            if (!$mysqli->query($sql)) {
                // Column likely exists already.
            }
        } catch (mysqli_sql_exception $e) {
        }
    }
}

function resize_image($source_path, $target_path, $max_width)
{
    $info = @getimagesize($source_path);
    if (!$info) {
        throw new RuntimeException("Cannot read image: {$source_path}");
    }

    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        default:
            throw new RuntimeException("Unsupported image type: {$source_path}");
    }

    if (!$source) {
        throw new RuntimeException("Cannot load image: {$source_path}");
    }

    $width = imagesx($source);
    $height = imagesy($source);

    if ($width <= $max_width) {
        $new_width = $width;
        $new_height = $height;
    } else {
        $new_width = $max_width;
        $new_height = (int) round($height * ($max_width / $width));
    }

    $target = imagecreatetruecolor($new_width, $new_height);

    if ($info[2] === IMAGETYPE_PNG) {
        imagealphablending($target, false);
        imagesavealpha($target, true);
    }

    imagecopyresampled($target, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            imagejpeg($target, $target_path, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($target, $target_path, 9);
            break;
    }

    imagedestroy($source);
    imagedestroy($target);
}
