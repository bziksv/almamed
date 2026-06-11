<?php

class shopSearchproV2SearchService
{
	private $settings;
	private $env;
	private $context;
	private $normalizer;
	private $pipeline;
	private $cache;
	private $util;
	private $finder;
	private $currency;
	private $query = '';

	public function __construct(shopSearchproV2Settings $settings, $context = 'dropdown')
	{
		$this->settings = $settings;
		$this->env = $settings->getEnv();
		$this->context = $context;
		$this->normalizer = new shopSearchproV2QueryNormalizer();
		$this->pipeline = new shopSearchproV2CorrectorPipeline();
		$this->util = new shopSearchproUtil();
	}

	public function suggest($query, $category_id = 0)
	{
		$this->query = $this->normalizer->normalize($query);
		$query = $this->query;
		$category_id = (int) $category_id;

		if ($query === '') {
			return new shopSearchproV2SuggestResult($query, $category_id, array(), 0);
		}

		$cache = $this->getResultCache();
		$cached = $cache->get($this->context, $query, $category_id);
		if ($cached !== null) {
			return new shopSearchproV2SuggestResult(
				$query,
				$category_id,
				ifset($cached, 'results', array()),
				ifset($cached, 'count', 0)
			);
		}

		$this->initFinder($category_id);

		$products = $this->hydrateProductEntities($this->findType('products'));
		$categories = $this->findType('categories', array(
			shopSearchproPluginFrontendDropdownController::class,
			'workupCategories',
		), array(
			'status' => (bool) $this->settings->get('dropdown_categories_products_status'),
			'seo_names' => $this->isCategoriesUseSeoNames(),
			'finder' => $this->finder,
			'products' => $products,
			'env' => $this->env,
		));
		$brands = $this->findType('brands');
		$popular = $this->findPopularFromCache();

		$data = array(
			'products' => $products,
			'categories' => $categories,
			'brands' => $brands,
			'history' => array(),
			'popular' => $popular,
		);

		$results = array();
		foreach ($this->settings->getEntitiesSort() as $entity) {
			if (array_key_exists($entity, $data)) {
				$results[$entity] = $data[$entity];
			}
		}

		$count = $this->finder->getCount('products');

		$cache->set($this->context, $query, $category_id, array(
			'results' => $results,
			'count' => $count,
		));

		return new shopSearchproV2SuggestResult($query, $category_id, $results, $count);
	}

	private function findPopularFromCache()
	{
		return shopSearchproV2Factory::popularService()->matchQuery($this->query);
	}

	/**
	 * Один batch SQL вместо N× shopProduct в suggest presenter.
	 */
	private function hydrateProductEntities(array $entities)
	{
		if (!$entities) {
			return $entities;
		}

		$product_ids = array();
		foreach ($entities as $entity) {
			$id = (int) ifset($entity, 'id', 0);
			if ($id) {
				$product_ids[] = $id;
			}
		}

		if (!$product_ids) {
			return $entities;
		}

		$collection = new shopSearchproProductsCollection($product_ids);

		$filled = $collection->getProductsFilled(count($product_ids), false);
		if (!$filled) {
			return $entities;
		}

		$result = array();
		foreach ($entities as $key => $entity) {
			$id = (int) ifset($entity, 'id', 0);
			if ($id && isset($filled[$id])) {
				$result[$key] = array_merge($filled[$id], array(
					'relevancy' => ifset($entity, 'relevancy', 0),
					'query' => ifset($entity, 'query', ''),
				));
			} else {
				$result[$key] = $entity;
			}
		}

		foreach ($result as &$entity) {
			$this->workupEntity($entity, 'products');
		}
		unset($entity);

		return $this->slimProductEntities($result);
	}

	private function slimProductEntities(array $entities)
	{
		$keep = array(
			'id' => true,
			'name' => true,
			'url' => true,
			'category_id' => true,
			'category_url' => true,
			'category_full_url' => true,
			'category_name' => true,
			'price' => true,
			'compare_price' => true,
			'currency' => true,
			'sku_price' => true,
			'sku_compare_price' => true,
			'image_id' => true,
			'image_filename' => true,
			'ext' => true,
			'relevancy' => true,
			'query' => true,
		);

		$slim = array();
		foreach ($entities as $key => $entity) {
			$row = array_intersect_key($entity, $keep);
			if (!empty($row)) {
				$slim[$key] = $row;
			}
		}

		return $slim;
	}

	private function initFinder($category_id)
	{
		$params = $this->pipeline->buildFinderParams(
			$this->settings,
			$this->context,
			$category_id,
			$this->env
		);
		$this->finder = new shopSearchproFinder($params);
	}

	private function findType($type, $handler = null, $handler_params = array())
	{
		$status = $this->settings->get("dropdown_{$type}_status");

		if (in_array($type, array('history', 'popular'), true)) {
			$status = $status && $this->settings->get("dropdown_{$type}_is_visible");
		}

		if (!$status) {
			return array();
		}

		$entities = $this->finder->find($type, $this->query)->getInitial();

		if (is_callable($handler, false, $callback)) {
			$entities = call_user_func($callback, $entities, $handler_params);
		}

		foreach ($entities as $id => &$entity) {
			if (!array_key_exists('query', $entity)) {
				$queries = $this->finder->getQueryForResultElement($type, $entity['id']);
				if (is_array($queries)) {
					$entity['query'] = implode(' ', $queries);
				}
			}
			if ($type !== 'products') {
				$this->workupEntity($entity, $type);
			}
		}
		unset($entity);

		return $entities;
	}

	private function workupEntity(&$entity, $type)
	{
		if ($type !== 'products') {
			return;
		}

		$currency = $this->getCurrency();

		if (!array_key_exists('currency', $entity) || $entity['currency'] === $currency) {
			return;
		}

		if (!array_key_exists('sku_price', $entity) || !array_key_exists('sku_compare_price', $entity)) {
			return;
		}

		$sku_price = (float) $entity['sku_price'];
		$sku_compare_price = (float) $entity['sku_compare_price'];

		if ($sku_compare_price === 0.0) {
			$entity['compare_price'] = 0.0;
		}

		$entity['price'] = shop_currency($sku_price, $entity['currency'], $currency, null);
		$entity['compare_price'] = shop_currency($sku_compare_price, $entity['currency'], $currency, null);
		$entity['price'] = shopRounding::roundCurrency($entity['price'], $currency);
		$entity['compare_price'] = shopRounding::roundCurrency($entity['compare_price'], $currency);
	}

	private function getCurrency()
	{
		if ($this->currency === null) {
			$this->currency = $this->env->getConfig()->getCurrency(false);
		}
		return $this->currency;
	}

	private function isCategoriesUseSeoNames()
	{
		if (!$this->env->isEnabledSeoPlugin()) {
			return false;
		}
		return (bool) $this->settings->get('dropdown_categories_seo_plugin_names');
	}

	private function getResultCache()
	{
		if ($this->cache === null) {
			$this->cache = new shopSearchproV2ResultCache($this->settings->getDropdownCacheTtl());
		}
		return $this->cache;
	}
}
