<?php
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/searchpro-plugin/suggest/?q=стетоскоп';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$root = dirname(__DIR__);
require_once $root . '/wa-config/SystemConfig.class.php';
$config = new SystemConfig('wa-config');
$wa = waSystem::getInstance(null, $config);
wa('shop');

$query = 'стетоскоп';
$times = array();

$settings = shopSearchproV2Settings::create();
$env = $settings->getEnv();
$pipeline = new shopSearchproV2CorrectorPipeline();

$t0 = microtime(true);
$params = $pipeline->buildFinderParams($settings, 'dropdown', 0, $env);
$times['buildFinderParams'] = microtime(true) - $t0;

$t0 = microtime(true);
$finder = new shopSearchproFinder($params);
$times['initFinder'] = microtime(true) - $t0;

$t0 = microtime(true);
$products_result = $finder->find('products', $query);
$products = $products_result->getInitial();
$times['find_products'] = microtime(true) - $t0;

$t0 = microtime(true);
$collection = new shopSearchproProductsCollection(array_column($products, 'id'));
$filled = $collection->getProductsFilled(count($products), false);
$times['hydrate_products'] = microtime(true) - $t0;

$t0 = microtime(true);
$categories_result = $finder->find('categories', $query);
$categories = $categories_result->getInitial();
$times['find_categories'] = microtime(true) - $t0;

$t0 = microtime(true);
$brands_result = $finder->find('brands', $query);
$brands = $brands_result->getInitial();
$times['find_brands'] = microtime(true) - $t0;

$t0 = microtime(true);
$popular = shopSearchproV2Factory::popularService()->matchQuery($query);
$times['popular'] = microtime(true) - $t0;

$t0 = microtime(true);
$count = $finder->getCount('products');
$times['getCount'] = microtime(true) - $t0;

$t0 = microtime(true);
$service = shopSearchproV2Factory::searchService('dropdown');
$result = $service->suggest($query, 0);
$times['suggest_full_cold'] = microtime(true) - $t0;

echo json_encode(array(
	'products' => count($products),
	'categories' => count($categories),
	'brands' => count($brands),
	'popular' => count($popular),
	'filled' => count($filled),
	'count' => $count,
	'times_ms' => array_map(function ($t) {
		return round($t * 1000);
	}, $times),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
