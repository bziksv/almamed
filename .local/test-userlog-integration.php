#!/usr/bin/env php
<?php
/**
 * Integration smoke tests for userlog shop plugin.
 * Prefer full suite: php .local/test-userlog-full.php
 * Run: php .local/test-userlog-integration.php
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
$root = dirname(__DIR__);
require $root.'/wa-system/autoload/waAutoload.class.php';
require $root.'/wa-config/SystemConfig.class.php';

$fail = 0;
$ok = function ($msg) { echo "  OK  {$msg}\n"; };
$bad = function ($msg) use (&$fail) { echo "  FAIL {$msg}\n"; $fail++; };

echo "=== userlog integration tests ===\n\n";

waSystem::getInstance(null, new SystemConfig());
wa('shop');

$plugin = shopUserlogPlugin::getInstance();
if ($plugin) {
    $ok('shopUserlogPlugin loaded');
} else {
    $bad('shopUserlogPlugin not available');
    exit(1);
}

$classes = array(
    'shopUserlogOrderSnapshot',
    'shopUserlogProductSnapshot',
    'shopUserlogCategorySnapshot',
    'shopUserlogSettingsSnapshot',
    'shopUserlogProductPageSnapshot',
    'shopUserlogProductServiceSnapshot',
    'shopUserlogSeoSnapshot',
    'shopUserlogSeofilterSnapshot',
    'userlogLogger',
    'userlogHelper',
);

foreach ($classes as $class) {
    if (in_array($class, array('userlogLogger', 'userlogHelper'), true)) {
        wa('userlog');
        wa('shop');
    }
    if (class_exists($class)) {
        $ok("class {$class}");
    } else {
        $bad("class {$class} missing");
    }
}

// Order snapshot
$order_model = new shopOrderModel();
$order_id = (int) $order_model->select('id')->order('id DESC')->limit(1)->fetchField('id');
if ($order_id) {
    try {
        userlogLogger::pullOrderBefore($order_id);
        $plugin->prepareOrderSave($order_id);
        $before = userlogLogger::pullOrderBefore($order_id);
        if ($before && !empty($before['order'])) {
            $ok("prepareOrderSave for order #{$order_id}");
        } else {
            $bad("prepareOrderSave returned empty snapshot for #{$order_id}");
        }
        $flat = shopUserlogOrderSnapshot::flattenForDiff($before);
        if (isset($flat['items'], $flat['total'])) {
            $ok('order flattenForDiff');
        } else {
            $bad('order flattenForDiff incomplete');
        }
        $snap = shopUserlogOrderSnapshot::captureForLog($order_id);
        if ($snap && count(ifset($snap, 'items', array())) > 0) {
            $ok('order snapshot captures items ('.count($snap['items']).')');
        } else {
            $bad('order snapshot items empty');
        }
    } catch (Exception $e) {
        $bad('order snapshot: '.$e->getMessage());
    }
} else {
    $bad('no orders in DB');
}

// Product snapshot
$product_id = (int) (new shopProductModel())->select('id')->order('id DESC')->limit(1)->fetchField('id');
if ($product_id) {
    try {
        userlogLogger::pullProductBefore($product_id);
        $plugin->prepareProductSave($product_id);
        $before = userlogLogger::pullProductBefore($product_id);
        if ($before) {
            $ok("prepareProductSave for product #{$product_id}");
        } else {
            $bad("prepareProductSave empty for #{$product_id}");
        }
    } catch (Exception $e) {
        $bad('product snapshot: '.$e->getMessage());
    }
}

// Category snapshot
$category_id = (int) (new shopCategoryModel())->select('id')->order('id DESC')->limit(1)->fetchField('id');
if ($category_id) {
    try {
        userlogLogger::pullCategoryBefore($category_id);
        $plugin->prepareCategorySave($category_id);
        $before = userlogLogger::pullCategoryBefore($category_id);
        if ($before) {
            $ok("prepareCategorySave for category #{$category_id}");
        } else {
            $bad("prepareCategorySave empty for #{$category_id}");
        }
    } catch (Exception $e) {
        $bad('category snapshot: '.$e->getMessage());
    }
}

// Settings diff
try {
    $diff = shopUserlogSettingsSnapshot::diff(array('a' => 1), array('a' => 2));
    if ($diff) {
        $ok('settings diff');
    } else {
        $bad('settings diff empty');
    }
} catch (Exception $e) {
    $bad('settings diff: '.$e->getMessage());
}

// Active app after userlog calls
if (wa()->getApp() === 'shop') {
    $ok('shop app context preserved');
} else {
    $bad('active app is '.wa()->getApp().', expected shop');
}

echo "\n";
if ($fail) {
    echo "FAILED: {$fail} test(s)\n";
    exit(1);
}
echo "All tests passed.\n";
exit(0);
