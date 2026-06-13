<?php
$root = dirname(__DIR__);
require $root.'/wa-system/autoload/waAutoload.class.php';
require $root.'/wa-config/SystemConfig.class.php';

$config = new SystemConfig('wa-config');
waSystem::getInstance(null, $config);
wa('shop');
wa('userlog');

$product_id = 39983;
$before = shopUserlogProductSnapshot::captureForLog($product_id);
if (!$before) {
    echo "Product not found\n";
    exit(1);
}

$flat = shopUserlogProductSnapshot::flattenForDiff($before);
echo "Flatten keys: ".implode(', ', array_keys($flat))."\n";
echo "Features count: ".count(ifset($flat, 'features', array()))."\n";
echo "Description excerpt: ".mb_substr(ifset($flat, 'description', ''), 0, 80)."\n";

$after = $before;
$after['product']['description'] = 'Новое описание для теста '.time();
$after['features']['brand'] = 'TestBrand';

$diff = userlogHelper::formatDiff(
    shopUserlogProductSnapshot::flattenForDiff($before),
    shopUserlogProductSnapshot::flattenForDiff($after),
    'product'
);

echo "Diff lines:\n";
foreach ($diff as $line) {
    echo ' - '.$line['label']."\n";
}

echo "Done\n";
