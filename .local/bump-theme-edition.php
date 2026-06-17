<?php
/**
 * Increment theme edition in theme.xml (site + shop child theme).
 * New ?v= in CSS/JS URLs → browsers fetch fresh assets after deploy.
 *
 * Usage: php .local/bump-theme-edition.php
 *        php .local/bump-theme-edition.php --dry-run
 */
$dry_run = in_array('--dry-run', $argv ?? [], true);

$themes = array(
    'site' => 'wa-data/public/site/themes/osnovnaja_new_header_footer_form/theme.xml',
    'shop' => 'wa-data/public/shop/themes/osnovnaja_new_header_footer_form/theme.xml',
);

$root = dirname(__DIR__);
$results = array();

foreach ($themes as $label => $rel) {
    $path = $root.'/'.$rel;
    if (!is_readable($path)) {
        fwrite(STDERR, "Missing: {$rel}\n");
        exit(1);
    }

    $xml = file_get_contents($path);
    if (!preg_match('/edition="(\d+)"/', $xml, $m)) {
        fwrite(STDERR, "No edition attribute in {$rel}\n");
        exit(1);
    }

    $old = (int) $m[1];
    $new = $old + 1;
    $new_xml = preg_replace('/edition="\d+"/', 'edition="'.$new.'"', $xml, 1);

    if (!$dry_run) {
        if (file_put_contents($path, $new_xml) === false) {
            fwrite(STDERR, "Failed to write {$rel}\n");
            exit(1);
        }
    }

    preg_match('/version="([^"]+)"/', $xml, $ver);
    $results[] = array(
        'label' => $label,
        'path' => $rel,
        'version' => isset($ver[1]) ? $ver[1] : '?',
        'edition' => $old.' → '.$new,
    );
}

$site_edition = (int) preg_replace('/.*edition="(\d+)".*/s', '$1', file_get_contents($root.'/'.$themes['site']));
$shop_edition = (int) preg_replace('/.*edition="(\d+)".*/s', '$1', file_get_contents($root.'/'.$themes['shop']));
if ($dry_run) {
    $site_edition = $site_edition + 1;
    $shop_edition = $shop_edition + 1;
}
$combined = $site_edition + $shop_edition;
$base_ver = preg_replace('/.*version="([^"]+)".*/s', '$1', file_get_contents($root.'/'.$themes['site']));

echo ($dry_run ? "[dry-run] " : '')."Theme cache-bust version: {$base_ver}.{$combined}\n";
foreach ($results as $r) {
    echo "  {$r['label']}: edition {$r['edition']} ({$r['path']})\n";
}
