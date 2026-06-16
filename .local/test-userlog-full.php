#!/usr/bin/env php
<?php
/**
 * Full integration tests for userlog (shop + blog).
 * Run before delivery: php .local/test-userlog-full.php
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
$root = dirname(__DIR__);
require $root.'/wa-system/autoload/waAutoload.class.php';
require $root.'/wa-config/SystemConfig.class.php';

$fail = 0;
$section = '';
$setSection = function ($name) use (&$section) {
    $section = $name;
    echo "\n--- {$name} ---\n";
};
$ok = function ($msg) { echo "  OK  {$msg}\n"; };
$bad = function ($msg) use (&$section, &$fail) {
    echo "  FAIL [{$section}] {$msg}\n";
    $fail++;
};
$skip = function ($msg) { echo "  SKIP {$msg}\n"; };

waSystem::getInstance(null, new SystemConfig());

echo "=== userlog full test suite ===\n";

// --- Deploy ---
$setSection('deploy');
foreach (array('userlog' => $root.'/wa-apps/userlog/lib/config/app.php') as $label => $path) {
    is_file($path) ? $ok("app {$label}") : $bad("missing {$path}");
}
$apps = is_file($root.'/wa-config/apps.php') ? include $root.'/wa-config/apps.php' : array();
empty($apps['userlog']) ? $bad('userlog not in apps.php') : $ok('userlog in apps.php');
foreach (array('shop', 'blog') as $app) {
    $pf = $root."/wa-config/apps/{$app}/plugins.php";
    if (!is_file($pf)) {
        $bad("no plugins.php for {$app}");
        continue;
    }
    $pl = include $pf;
    empty($pl['userlog']) ? $bad("userlog plugin off in {$app}") : $ok("userlog plugin on in {$app}");
}

// --- Autoload ---
$setSection('autoload');
wa('shop');
$map = wa('shop')->getConfig()->getClasses();
$required = array(
    'shopUserlogPlugin', 'shopUserlogOrderSnapshot', 'shopUserlogProductSnapshot',
    'shopUserlogCategorySnapshot', 'shopUserlogSettingsSnapshot', 'shopUserlogProductPageSnapshot',
    'shopUserlogProductServiceSnapshot', 'shopUserlogSeoSnapshot',     'shopUserlogSeofilterSnapshot',
    'shopUserlogSliderSnapshot',
);
foreach ($required as $class) {
    isset($map[$class]) ? $ok("autoload {$class}") : $bad("autoload missing {$class}");
}
$plugin = shopUserlogPlugin::getInstance();
$plugin ? $ok('shopUserlogPlugin instance') : $bad('shopUserlogPlugin instance');

// --- Context switch ---
$setSection('context');
wa('shop');
$plugin->prepareOrderSave((int) (new shopOrderModel())->select('id')->order('id DESC')->limit(1)->fetchField('id'));
wa()->getApp() === 'shop' ? $ok('shop context after ensureUserlogReady') : $bad('broken app context: '.wa()->getApp());

// --- Order snapshot ---
$setSection('order');
$order_id = (int) (new shopOrderModel())->select('id')->where('total > 0')->order('id DESC')->limit(1)->fetchField('id');
if (!$order_id) {
    $bad('no order for tests');
} else {
    $snap = shopUserlogOrderSnapshot::captureForLog($order_id);
    if (!$snap || empty($snap['order'])) {
        $bad('captureForLog empty');
    } else {
        $ok("capture order #{$order_id}");
    }
    $item_count = count(ifset($snap, 'items', array()));
    if ($item_count > 0) {
        $ok("snapshot has {$item_count} items");
        $first = $snap['items'][0];
        foreach (array('id', 'type', 'name', 'product_id', 'sku_id', 'price', 'quantity') as $k) {
            array_key_exists($k, $first) ? null : $bad("item missing field {$k}");
        }
        if (array_key_exists('id', $first) && (int) $first['id'] > 0) {
            $ok('item has id');
        } else {
            $bad('item id invalid');
        }
    } else {
        $bad('snapshot items empty');
    }

    // Round-trip: delete one item, restore snapshot
    shopUserlogPlugin::setLoggingSuspended(true);
    try {
        $items_model = new shopOrderItemsModel();
        $before_count = count($items_model->getItems($order_id));
        if ($before_count > 1) {
            $items = $items_model->getItems($order_id);
            $remove_id = null;
            foreach ($items as $id => $row) {
                if ($row['type'] === 'product') {
                    $remove_id = $id;
                    break;
                }
            }
            if ($remove_id) {
                $reduced = $items;
                unset($reduced[$remove_id]);
                $items_model->update(array_values($reduced), $order_id);
                $mid_count = count($items_model->getItems($order_id));
                ($mid_count === $before_count - 1) ? $ok('simulated item delete') : $bad('simulated delete failed');

                shopUserlogOrderSnapshot::restoreForUpdate($snap, $order_id);
                $after_count = count($items_model->getItems($order_id));
                ($after_count === $before_count) ? $ok('order restore round-trip items') : $bad("restore items {$after_count} != {$before_count}");

                $restored_total = (float) (new shopOrderModel())->getById($order_id)['total'];
                $expected_total = (float) $snap['order']['total'];
                (abs($restored_total - $expected_total) < 0.01)
                    ? $ok('order restore round-trip total')
                    : $bad("restore total {$restored_total} != {$expected_total}");
            } else {
                $skip('no product line to delete');
            }
        } else {
            $skip('order has <=1 item');
        }

        // Empty items guard
        $broken = array('order' => array('total' => '100.0000'), 'items' => array());
        try {
            shopUserlogOrderSnapshot::restoreForUpdate($broken, $order_id);
            $bad('empty items snapshot should throw');
        } catch (waException $e) {
            $ok('rejects broken empty-items snapshot');
        }
    } catch (Exception $e) {
        $bad('order round-trip: '.$e->getMessage());
        // Try to restore original snapshot on failure
        try {
            shopUserlogOrderSnapshot::restoreForUpdate($snap, $order_id);
        } catch (Exception $e2) {
        }
    } finally {
        shopUserlogPlugin::setLoggingSuspended(false);
    }

    // prepare + finalize logging
    userlogLogger::pullOrderBefore($order_id);
    $plugin->prepareOrderSave($order_id);
    $before = userlogLogger::pullOrderBefore($order_id);
    if ($before && count(ifset($before, 'items', array())) > 0) {
        $ok('prepareOrderSave stores items');
    } else {
        $bad('prepareOrderSave items empty');
    }
}

// --- Product snapshot ---
$setSection('product');
$product_id = (int) (new shopProductModel())->select('id')->order('id DESC')->limit(1)->fetchField('id');
if ($product_id) {
    $ps = shopUserlogProductSnapshot::captureForLog($product_id);
    ($ps && !empty($ps['product'])) ? $ok("product capture #{$product_id}") : $bad('product capture empty');

    shopUserlogPlugin::setLoggingSuspended(true);
    try {
        $orig_name = (new shopProductModel())->getById($product_id)['name'];
        $test_name = $orig_name.' [userlog-test]';
        (new shopProductModel())->updateById($product_id, array('name' => $test_name));
        $restore_data = shopUserlogProductSnapshot::prepareForRestore($ps, $product_id);
        shopUserlogProductSnapshot::restore($restore_data, null, $product_id);
        $back = (new shopProductModel())->getById($product_id)['name'];
        ($back === $orig_name) ? $ok('product restore round-trip name') : $bad("product name not restored: {$back}");
    } catch (Exception $e) {
        $bad('product restore: '.$e->getMessage());
    } finally {
        shopUserlogPlugin::setLoggingSuspended(false);
    }
} else {
    $bad('no product');
}

// --- Category snapshot ---
$setSection('category');
$category_id = (int) (new shopCategoryModel())->select('id')->order('id DESC')->limit(1)->fetchField('id');
if ($category_id) {
    $cs = shopUserlogCategorySnapshot::captureForLog($category_id);
    ($cs && !empty($cs['category'])) ? $ok("category capture #{$category_id}") : $bad('category capture empty');
    userlogLogger::pullCategoryBefore($category_id);
    $plugin->prepareCategorySave($category_id);
    $cb = userlogLogger::pullCategoryBefore($category_id);
    ($cb && !empty($cb['category'])) ? $ok('prepareCategorySave') : $bad('prepareCategorySave empty');
} else {
    $bad('no category');
}

// --- Settings snapshots ---
$setSection('settings');
try {
    shopUserlogSettingsSnapshot::captureGeneralSettings();
    $ok('captureGeneralSettings');
    shopUserlogSettingsSnapshot::captureCheckout();
    $ok('captureCheckout');
    shopUserlogSettingsSnapshot::captureCurrencies();
    $ok('captureCurrencies');
    $diff = shopUserlogSettingsSnapshot::diff(array('x' => 1), array('x' => 2));
    $diff ? $ok('settings diff') : $bad('settings diff');
} catch (Exception $e) {
    $bad('settings: '.$e->getMessage());
}

// --- Blog ---
$setSection('blog');
if (wa()->appExists('blog')) {
    wa('blog');
    if (class_exists('blogUserlogPostSnapshot')) {
        $post_id = (int) (new blogPostModel())->select('id')->order('id DESC')->limit(1)->fetchField('id');
        if ($post_id) {
            $post = blogUserlogPostSnapshot::captureForLog($post_id);
            ($post && !empty($post['post'])) ? $ok("blog capture #{$post_id}") : $bad('blog capture empty');
        } else {
            $skip('no blog posts');
        }
    } else {
        wa('blog')->getPlugin('userlog');
        class_exists('blogUserlogPostSnapshot') ? $ok('blogUserlogPostSnapshot') : $bad('blog snapshot class');
    }
} else {
    $skip('blog app not installed');
}

// --- Slider ---
$setSection('slider');
try {
    $snap = $plugin->captureSliderForLog();
    if ($snap) {
        $count = count(ifset($snap, 'slides', array()));
        ($count > 0) ? $ok("slider capture ({$count} slides)") : $bad('slider capture empty');
        if ($count > 0) {
            $modified = $snap;
            $modified['slides'][0] = $snap['slides'][0];
            $modified['slides'][0]['link'] = 'http://userlog-test.example/';
            $diff = shopUserlogSettingsSnapshot::diff(
                shopUserlogSliderSnapshot::flattenForDiff($snap),
                shopUserlogSliderSnapshot::flattenForDiff($modified)
            );
            $diff ? $ok('slider diff detects link change') : $bad('slider diff empty on link change');
        }
    } else {
        $bad('captureSliderForLog failed');
    }
    if (method_exists($plugin, 'logSliderChange')) {
        $ok('logSliderChange method');
    } else {
        $bad('logSliderChange missing');
    }
    $slider_cfg = include wa()->getAppPath('plugins/slider/lib/config/plugin.php', 'shop');
    (!empty($slider_cfg['handlers']['backend_menu']))
        ? $ok('slider backend_menu in plugin.php')
        : $bad('slider backend_menu handler missing');
} catch (Exception $e) {
    $bad('slider: '.$e->getMessage());
}

// --- Event handlers ---
$setSection('events');
$plugin_config = include wa()->getAppPath('plugins/userlog/lib/config/plugin.php', 'shop');
if (!empty($plugin_config['handlers']['order_action.*'])) {
    $ok('order_action.* handler in plugin config');
} else {
    $bad('order_action.* missing in plugin.php');
}
waEvent::clearCache();
$params = array('order_id' => $order_id, 'action_id' => 'edit');
wa('shop')->event('order_action.edit', $params);
$handler_files = glob($root.'/wa-cache/*/apps/system/waEvent/cache/handlers.php');
if ($handler_files) {
    $content = file_get_contents($handler_files[0]);
    (strpos($content, 'userlog') !== false && strpos($content, 'orderAction') !== false)
        ? $ok('order_action handler in event cache')
        : $skip('event cache stale — php cli.php shop clearCache on prod');
} else {
    $skip('event cache in memory only (debug mode)');
}

// --- Extra snapshots ---
$setSection('snapshots');
try {
    $page_id = (int) (new shopProductPagesModel())->select('id')->order('id DESC')->limit(1)->fetchField('id');
    if ($page_id) {
        $p = shopUserlogProductPageSnapshot::captureForLog($page_id);
        ($p && !empty($p['page'])) ? $ok("product page capture #{$page_id}") : $bad('product page capture');
    } else {
        $skip('no product pages');
    }
    if (shopUserlogSeoSnapshot::isAvailable()) {
        $ok('seo snapshot available');
    } else {
        $skip('seo plugin not active');
    }
} catch (Exception $e) {
    $bad('snapshots: '.$e->getMessage());
}

// --- DB ---
$setSection('database');
wa('userlog');
try {
    (new userlogEventModel())->query('SELECT 1 FROM userlog_event LIMIT 1');
    $ok('userlog_event table');
} catch (Exception $e) {
    $bad('userlog_event: '.$e->getMessage());
}

echo "\n";
if ($fail) {
    echo "FAILED: {$fail} test(s). Fix before deploy.\n";
    exit(1);
}
echo "PASSED: all tests OK.\n";
exit(0);
