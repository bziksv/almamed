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

		if ($query === '') {
			return $result;
		}

		$products = ifset($result->results, 'products', array());
		$strong = $result->count > 0 && !shopSearchproV2TypoHelper::isWeakMatch($query, $products);
		if ($strong) {
			return $result;
		}

		foreach ($this->buildFallbackQueries($query) as $corrected_query) {
			$retry = $this->suggestOnce($corrected_query, $category_id);
			if ($retry->count <= 0) {
				continue;
			}
			$retry_products = ifset($retry->results, 'products', array());
			if (!shopSearchproV2TypoHelper::isWeakMatch($corrected_query, $retry_products)) {
				return $retry;
			}
			// Keep first non-empty weak hit only if original was empty.
			if ($result->count <= 0) {
				$result = $retry;
			}
		}

		return $result;
	}

	/**
	 * Typo + keyboard-layout (+ combos). Bounded list for suggest latency.
	 *
	 * @return string[]
	 */
	private function buildFallbackQueries($query)
	{
		$query = trim((string) $query);
		$out = array();
		$seen = array($query => true);

		$push = function ($candidate) use (&$out, &$seen) {
			$candidate = trim((string) $candidate);
			if ($candidate === '' || isset($seen[$candidate])) {
				return;
			}
			$seen[$candidate] = true;
			$out[] = $candidate;
		};

		$typo = shopSearchproV2TypoHelper::candidates($query, $this->settings);
		$layout = shopSearchproV2KeyboardLayoutHelper::candidates($query);

		// Typo first, and right after each — layout flip («отоскоп лфцу» → «отоскоп kawe»).
		foreach (array_slice($typo, 0, 20) as $ty) {
			$push($ty);
			foreach (array_slice(shopSearchproV2KeyboardLayoutHelper::candidates($ty), 0, 3) as $c) {
				$push($c);
			}
		}

		foreach (array_slice($layout, 0, 6) as $kb) {
			$push($kb);
			foreach (array_slice(shopSearchproV2TypoHelper::candidates($kb, $this->settings), 0, 8) as $c) {
				$push($c);
			}
		}

		return array_slice($out, 0, 28);
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
			'sku_id' => true,
			'sku_count' => true,
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

		$images_by_category = $this->buildDropdownCategoryImages($products);

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

	/**
	 * Thumbnail per category from the first suggest product linked via shop_category_products.
	 *
	 * @param array $products
	 * @return array<int, string>
	 */
	private function buildDropdownCategoryImages(array $products)
	{
		$images_by_category = array();
		$product_images = array();
		$product_ids = array();

		foreach ($products as $product) {
			$product_id = (int) ifset($product, 'id', 0);
			if (!$product_id || empty($product['image_id']) || isset($product_images[$product_id])) {
				continue;
			}

			$product_ids[] = $product_id;
			$product_images[$product_id] = shopImage::getUrl(array(
				'product_id' => $product_id,
				'id' => (int) $product['image_id'],
				'filename' => ifset($product, 'image_filename', ''),
				'ext' => ifset($product, 'ext', 'jpg'),
			), '48x48');
		}

		if (!$product_ids) {
			return $images_by_category;
		}

		$category_products_model = new shopCategoryProductsModel();
		$rows = $category_products_model->query(
			'SELECT product_id, category_id FROM ' . $category_products_model->getTableName()
			. ' WHERE product_id IN (i:ids)',
			array('ids' => $product_ids)
		)->fetchAll();

		$category_ids_by_product = array();
		foreach ($rows as $row) {
			$product_id = (int) ifset($row, 'product_id', 0);
			$category_id = (int) ifset($row, 'category_id', 0);
			if ($product_id && $category_id) {
				$category_ids_by_product[$product_id][] = $category_id;
			}
		}

		foreach ($products as $product) {
			$product_id = (int) ifset($product, 'id', 0);
			if (!$product_id || !isset($product_images[$product_id])) {
				continue;
			}

			$linked_category_ids = ifset($category_ids_by_product, $product_id, array());
			if (!$linked_category_ids) {
				$primary_category_id = (int) ifset($product, 'category_id', 0);
				if ($primary_category_id) {
					$linked_category_ids = array($primary_category_id);
				}
			}

			foreach ($linked_category_ids as $category_id) {
				if (!isset($images_by_category[$category_id])) {
					$images_by_category[$category_id] = $product_images[$product_id];
				}
			}
		}

		return $images_by_category;
	}
}
