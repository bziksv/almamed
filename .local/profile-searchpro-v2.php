<?php
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/searchpro-plugin/suggest/?q=стетоскоп';
$_SERVER['REQUEST_METHOD'] = 'GET';

$root = dirname(__DIR__);
require_once $root . '/wa-config/SystemConfig.class.php';
$config = new SystemConfig('wa-config');
waSystem::getInstance(null, $config);
wa('shop');

$query = 'стетоскоп';
$times = array();

$t0 = microtime(true);
$service = shopSearchproV2Factory::searchService('dropdown');
$result = $service->suggest($query, 0);
$times['suggest_cold'] = microtime(true) - $t0;

$t0 = microtime(true);
$result = $service->suggest($query, 0);
$times['suggest_warm'] = microtime(true) - $t0;

$products = ifset($result->results, 'products', array());

echo json_encode(array(
    'use_v2' => shopSearchproV2Settings::isUseV2(),
    'count' => $result->count,
    'products' => count($products),
    'times_ms' => array_map(function ($t) { return round($t * 1000); }, $times),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
