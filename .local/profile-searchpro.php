<?php
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/search/?query=стетоскоп';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['query'] = 'стетоскоп';

$root = dirname(__DIR__);
require_once $root . '/wa-config/SystemConfig.class.php';
$config = new SystemConfig('wa-config');
waSystem::getInstance(null, $config);
wa('shop');

$query = 'стетоскоп';
$plugin = wa('shop')->getPlugin('searchpro');
$times = array();

$t0 = microtime(true);
$finder = new shopSearchproFinder(array(
    'mode' => $plugin->getSettings('search_mode'),
    'slice_query' => $plugin->getSettings('search_slice_query'),
    'cache_type' => 'page',
    'cache_actuality' => (int) $plugin->getSettings('page_results_cache'),
    'category_id' => 0,
    'corrector_status' => $plugin->getSettings('corrector_status'),
    'match_status' => $plugin->getSettings('match_status'),
));
$result = $finder->find('products', $query);
$times['finder'] = microtime(true) - $t0;

$collection = $result->getInitialCollection();
$hash = $collection->getHash();

$t0 = microtime(true);
$collection->filters(waRequest::get());
$times['filters'] = microtime(true) - $t0;

$t0 = microtime(true);
$count = $collection->count();
$times['count'] = microtime(true) - $t0;

$t0 = microtime(true);
$products = $collection->getProducts('*,skus_filtered,skus_image', 0, 30);
$times['getProducts30'] = microtime(true) - $t0;

$t0 = microtime(true);
$util = new shopSearchproUtil();
$categories = $util->getCollectionCategories($collection);
$times['getCollectionCategories'] = microtime(true) - $t0;

if (is_array($hash) && $hash[0] === 'id') {
    $ids = array_slice(explode(',', $hash[1]), 0, 600);
    $t0 = microtime(true);
    $categories_fast = $util->getCategoriesByProductIds($ids);
    $times['getCategoriesByProductIds'] = microtime(true) - $t0;
}

echo json_encode(array(
    'result_count' => $result->getCount(),
    'filtered_count' => $count,
    'products_page' => count($products),
    'categories' => count($categories),
    'hash_type' => is_array($hash) ? $hash[0] : null,
    'hash_ids' => (is_array($hash) && $hash[0] === 'id') ? count(explode(',', $hash[1])) : null,
    'times_ms' => array_map(function ($t) { return round($t * 1000); }, $times),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
