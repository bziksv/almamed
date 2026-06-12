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
		$result = $this->suggestOnce($query, $category_id);

		if ($query !== '') {
			$typo_fixed = $this->tryTypoCandidates($query, $category_id, $result);
			if ($typo_fixed !== null) {
				return $typo_fixed;
			}
		}

		if ($result->count > 0 || $query === '') {
			return $result;
		}

		foreach (shopSearchproV2KeyboardLayoutHelper::candidates($query) as $corrected_query) {
			if ($corrected_query === $query) {
				continue;
			}
			$retry = $this->suggestOnce($corrected_query, $category_id);
			if ($retry->count > 0) {
				return $retry;
			}
		}

		return $result;
	}

	/**
	 * @param shopSearchproV2SuggestResult|null $current
	 * @return shopSearchproV2SuggestResult|null
	 */
	private function tryTypoCandidates($query, $category_id, shopSearchproV2SuggestResult $current = null)
	{
		$products = $current ? ifset($current->results, 'products', array()) : array();
		if ($current && !shopSearchproV2TypoHelper::isWeakMatch($query, $products)) {
			return null;
		}

		foreach (shopSearchproV2TypoHelper::candidates($query, $this->settings) as $corrected_query) {
			$retry = $this->suggestOnce($corrected_query, $category_id);
			if ($retry->count <= 0) {
				continue;
			}
			if (!shopSearchproV2TypoHelper::isWeakMatch($corrected_query, ifset($retry->results, 'products', array()))) {
				return $retry;
			}
		}

		return null;
	}

	private function suggestOnce($query, $category_id = 0)
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

		$product_entities = $this->findType('products');
		$products = $this->hydrateProductEntities($product_entities);

		$categories_from_products = (bool) $this->settings->get('dropdown_categories_products_status') && $products;
		if ($categories_from_products) {
			$categories = shopSearchproPluginFrontendDropdownController::workupCategories(array(), array(
				'status' => true,
				'seo_names' => $this->isCategoriesUseSeoNames(),
				'finder' => $this->finder,
				'products' => $products,
				'env' => $this->env,
			));
		} else {
			$categories = $this->findType('categories', array(
				shopSearchproPluginFrontendDropdownController::class,
				'workupCategories',
			), array(
				'status' => false,
				'seo_names' => $this->isCategoriesUseSeoNames(),
				'finder' => $this->finder,
				'products' => $products,
				'env' => $this->env,
			));
		}

		$categories = $this->prepareDropdownCategories($categories, $products);

		$brands = ($products || $categories)
			? $this->findType('brands')
			: array();
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

		$filled = $collection->getProductsSuggest(count($product_ids));
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

	/**
	 * @param array $categories
	 * @param array $products
	 * @return array
	 */
	private function prepareDropdownCategories(array $categories, array $products)
	{
		if (!$categories) {
			return array();
		}

		$images_by_category = array();
		foreach ($products as $product) {
			$category_id = (int) ifset($product, 'category_id', 0);
			if (!$category_id || isset($images_by_category[$category_id]) || empty($product['image_id'])) {
				continue;
			}
			$images_by_category[$category_id] = shopImage::getUrl(array(
				'product_id' => (int) ifset($product, 'id', 0),
				'id' => (int) $product['image_id'],
				'filename' => ifset($product, 'image_filename', ''),
				'ext' => ifset($product, 'ext', 'jpg'),
			), '48x48');
		}

		foreach ($categories as &$category) {
			$category_id = (int) ifset($category, 'id', 0);
			if ($category_id && isset($images_by_category[$category_id])) {
				$category['image_url'] = $images_by_category[$category_id];
			}
			if (empty($category['count'])) {
				$category['count'] = (int) ifset($category, 'count', 0);
			}
		}
		unset($category);

		usort($categories, function ($a, $b) {
			$count_a = (int) ifset($a, 'count', 0);
			$count_b = (int) ifset($b, 'count', 0);
			if ($count_a !== $count_b) {
				return $count_b - $count_a;
			}
			return strcmp(ifset($a, 'name', ''), ifset($b, 'name', ''));
		});

		$max = (int) $this->settings->get('dropdown_categories_max_count');
		if ($max > 0 && count($categories) > $max) {
			$categories = array_slice($categories, 0, $max);
		}

		return $categories;
	}
}
