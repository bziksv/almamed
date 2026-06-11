<?php
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/category/veterinariya/';
$_SERVER['REQUEST_METHOD'] = 'GET';

$root = dirname(__DIR__);
require_once $root . '/wa-config/SystemConfig.class.php';
$config = new SystemConfig('wa-config');
waSystem::getInstance(null, $config);
wa('shop');

waRequest::setParam('action', 'category');
waRequest::setParam('category_url', 'veterinariya');
waRequest::setParam('url_type', 0);

$plugin = wa('shop')->getPlugin('searchpro');
$settings = shopSearchproPlugin::staticallyGetSettings();
$times = array();

$t0 = microtime(true);
$route = (new shopSearchproEnv())->getCurrentStorefront();
$times['getCurrentStorefront'] = microtime(true) - $t0;

$t0 = microtime(true);
$cats = (new shopCategoryModel())->getTree(0, 1, true, $route);
$times['getTree_depth1'] = microtime(true) - $t0;

$t0 = microtime(true);
$popular = (new shopSearchproQueryModel())->getVisible(5);
$times['getVisible_popular'] = microtime(true) - $t0;

$t0 = microtime(true);
$frontend = new shopSearchproFrontend(null, $settings, shopSearchproPlugin::getEnv());
$times['construct'] = microtime(true) - $t0;

$t0 = microtime(true);
$html = $frontend->field();
$times['field_total'] = microtime(true) - $t0;

preg_match_all('/searchpro-plugin\/config[^"\']+/', $html, $config_urls);
preg_match('/\?(\d+)/', $html, $rand_match);

echo json_encode(array(
    'categories_in_tree' => count($cats),
    'popular_queries' => count($popular),
    'html_bytes' => strlen($html),
    'config_urls' => $config_urls[0],
    'has_rand_cache_bust' => (bool) preg_match('/config\/\?v[\d.]+\?\d+/', $html),
    'settings' => array(
        'category_filter_status' => $settings['category_filter_status'] ?? null,
        'category_filter_deep' => $settings['category_filter_deep'] ?? null,
        'dropdown_popular_is_visible' => $settings['dropdown_popular_is_visible'] ?? null,
        'page_results_cache' => $settings['page_results_cache'] ?? null,
        'dropdown_results_cache' => $settings['dropdown_results_cache'] ?? null,
    ),
    'times_ms' => array_map(function ($t) { return round($t * 1000, 2); }, $times),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
