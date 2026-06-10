<?php

class shopSearchproPluginFrontendDropdownController extends waController
{
	private $finder;
	private $plugin;
	private $results;
	private $count;

	protected $env;
	protected static $util;
	protected $frontend;
	protected $currency;

	public $query;
	public $category_id;
	public $format;

	public function __construct()
	{
		$this->plugin = shopSearchproPlugin::getInstance();

		$this->query = shopSearchproPluginHelper::prepareQuery(waRequest::get('q'));

		$this->category_id = waRequest::get('category_id', 0, 'int');
		$this->format = waRequest::get('format', 'html');
	}

	private function getPlugin()
	{
		return $this->plugin;
	}

	private function getSettings($name = null)
	{
		return $this->getPlugin()->getSettings($name);
	}

	protected function getFrontend()
	{
		if(!isset($this->frontend)) {
			$this->frontend = new shopSearchproFrontend(null, $this->getSettings());
		}

		return $this->frontend;
	}

	/**
	 * @return string
	 */
	protected function getCurrency()
	{
		if(!isset($this->currency)) {
			$this->currency = $this->getEnv()->getConfig()->getCurrency(false);
		}

		return $this->currency;
	}

	private function setResults($results)
	{
		$this->results = $results;
	}

	private function getResults()
	{
		return $this->results;
	}

	private function setCount($count)
	{
		$this->count = $count;
	}

	private function getCount()
	{
		return $this->count;
	}

	/**
	 * @return shopSearchproFinder
	 */
	private function getFinder()
	{
		return $this->finder;
	}

	protected function getEnv()
	{
		if(!isset($this->env)) {
			$this->env = new shopSearchproEnv();
		}

		return $this->env;
	}

	protected static function getUtil()
	{
		if(!isset(self::$util)) {
			self::$util = new shopSearchproUtil();
		}

		return self::$util;
	}

	public function preExecute()
	{
		$params = array(
			'mode' => $this->getSettings('search_mode'),
			'slice_query' => $this->getSettings('search_slice_query'),
			'rest_words' => $this->getSettings('search_rest_words'),
			'word_forms' => $this->getSettings('search_word_forms'),
			'form_break_symbols' => $this->getSettings('search_form_break_symbols'),
			'form_numbers' => $this->getSettings('search_form_numbers'),
			'form_strnum' => $this->getSettings('search_form_strnum'),
			'form_ignore_numstart' => $this->getSettings('search_form_ignore_numstart'),
			'form_min_length' => $this->getSettings('search_form_min_length'),
			'cache_type' => 'dropdown',
			'cache_actuality' => $this->getSettings('dropdown_results_cache'),
			'category_id' => $this->category_id,
			'match_status' => $this->getSettings('match_status'),
			'brands_plugin' => $this->getSettings('dropdown_brands_plugin'),
			'corrector_status' => $this->getSettings('corrector_status'),
			'counts' => array(
				'products' => array(
					'min' => (int) $this->getSettings('dropdown_products_min_count'),
					'max' => (int) $this->getSettings('dropdown_products_max_count')
				),
				'categories' => array(
					'min' => (int) $this->getSettings('dropdown_categories_min_count'),
					'max' => (int) $this->getSettings('dropdown_categories_max_count')
				),
				'brands' => array(
					'max' => (int) $this->getSettings('dropdown_brands_max_count')
				),
				'popular' => array(
					'max' => (int) $this->getSettings('dropdown_popular_max_count')
				),
				'history' => array(
					'max' => (int) $this->getSettings('dropdown_history_max_count')
				)
			),
			'fields' => array(
				'products' => array(
					'filled' => true,
					'event_frontend_products' => $this->getSettings('dropdown_event_frontend_products'),
					'pages' => $this->getSettings('dropdown_products_pages_status'),
					'seopage_plugin' => $this->getEnv()->isEnabledSeopagePlugin() && $this->getSettings('dropdown_products_seopage_plugin_status')
				),
				'categories' => array(
					'hide_hidden' => $this->getSettings('dropdown_categories_hidden_hide_status'),
					'names' => $this->getSettings('dropdown_categories_names_status'),
					'descriptions' => $this->getSettings('dropdown_categories_descriptions_status'),
					'seo_plugin' => $this->getEnv()->isEnabledSeoPlugin() && $this->getSettings('dropdown_categories_seo_plugin_status'),
					'seo_names' => $this->getEnv()->isEnabledSeoPlugin() && $this->getSettings('dropdown_categories_seo_plugin_names'),
					//'seofilter_plugin' => $this->getSettings('dropdown_categories_seofilter_plugin_status')
				)
			),
			'translate_status' => $this->getSettings('translate_status'),
			'grams_status' => $this->getSettings('grams_status'),
			'grams_mode' => $this->getSettings('grams_mode'),
			'keyboard_layout_status' => $this->getSettings('keyboard_layout_status'),
			'keyboard_layout_mode' => $this->getSettings('keyboard_layout_mode'),
			'combine_status' => $this->getSettings('combine_status'),
		);

		$this->finder = new shopSearchproFinder($params);
	}

	/**
	 * @param string $type
	 * @param callable|null $handler
	 * @param array $handler_params
	 * @return array
	 * @throws waException
	 */
	protected function find($type, $handler = null, $handler_params = array())
	{
		$status = $this->getSettings("dropdown_{$type}_status");

		if(in_array($type, array('history', 'popular'))) {
			$status = $status && $this->getSettings("dropdown_{$type}_is_visible");
		}

		if(!$status) {
			return array();
		}

		$finder = $this->getFinder();

		$entities = $finder->find($type, $this->query)->getInitial();

		if(is_callable($handler, false, $callback)) {
			$entities = call_user_func($callback, $entities, $handler_params);
		}

		foreach($entities as $id => &$entity) {
			if(!array_key_exists('query', $entity)) {
				$queries = $finder->getQueryForResultElement($type, $entity['id']);

				if(is_array($queries)) {
					$entity['query'] = implode(' ', $queries);
				}
			}

			$this->workupEntity($entity, $type);
		}

		return $entities;
	}

	/**
	 * @param array $entity
	 * @param string $type
	 */
	private function workupEntity(&$entity, $type)
	{
		if($type === 'products') {
			$currency = $this->getCurrency();

			if(!array_key_exists('currency', $entity)) {
				return;
			}

			if($entity['currency'] === $currency) {
				return;
			}

			if(!array_key_exists('sku_price', $entity) || !array_key_exists('sku_compare_price', $entity)) {
				return;
			}

			$sku_price = (float) $entity['sku_price'];
			$sku_compare_price = (float) $entity['sku_compare_price'];

			if($sku_compare_price === 0.0) {
				$entity['compare_price'] = 0.0;
			}

			$entity['price'] = shop_currency($sku_price, $entity['currency'], $currency, null);
			$entity['compare_price'] = shop_currency($sku_compare_price, $entity['currency'], $currency, null);

			$entity['price'] = shopRounding::roundCurrency($entity['price'], $currency);
			$entity['compare_price'] = shopRounding::roundCurrency($entity['compare_price'], $currency);
		}
	}

	public static function workupCategories($categories, array $params = array())
	{
		/**
		 * @var shopSearchproFinder $finder
		 * @var shopSearchproEnv $env
		 */

		$status = $params['status'];
		$seo_names = $params['seo_names'];
		$finder = $params['finder'];
		$products = $params['products'];
		$env = $params['env'];

		$categories_by_id = array();
		foreach ($categories as $category)
		{
			$categories_by_id[$category['id']] = $category;
		}

		if($status && $products_collection = $finder->getOutputCollection('products')) {
			$products_categories = self::getUtil()->getProductsCategories($products);

			$categories = self::getUtil()->replace($categories_by_id, $products_categories);
		}

		$current_storefront = $env->getCurrentStorefront();
		$category_routes_model = new shopCategoryRoutesModel();

		foreach($categories as $key => &$category) {
			$routes = $category_routes_model->getRoutes($category['id']);

			if(!empty($routes) && !in_array($current_storefront, $routes)) {
				unset($categories[$key]);
				continue;
			}

			if($seo_names) {
				if(!empty($category['seo_name'])) {
					$category['name'] = $category['seo_name'];
				}
			}
		}

		return $categories;
	}

	protected function isCategoriesUseSeoNames()
	{
		$is_enabled_seo_plugin = $this->getEnv()->isEnabledSeoPlugin();

		if($is_enabled_seo_plugin) {
			return (bool) $this->getSettings('dropdown_categories_seo_plugin_names');
		}

		return false;
	}

	public function execute()
	{
		$products = $this->find('products');

		$categories = $this->find('categories', array($this, 'workupCategories'), array(
			'status' => $this->getSettings('dropdown_categories_products_status'),
			'seo_names' => $this->isCategoriesUseSeoNames(),
			'finder' => $this->getFinder(),
			'products' => $products,
			'env' => $this->getEnv()
		));

		$brands = $this->find('brands');
		$history = $this->find('history');
		$popular = $this->find('popular');
		$data = array(
			'products' => $products,
			'categories' => $categories,
			'brands' => $brands,
			'history' => $history,
			'popular' => $popular
		);

		$results = array();

		$sort = $this->getSettings('dropdown_entities_sort');
		foreach($sort as $entity) {
			if(array_key_exists($entity, $data)) {
				$results[$entity] = $data[$entity];
			}
		}

		$this->setResults($results);

		$this->setCount($this->getFinder()->getCount('products'));

		$this->output();
	}

	protected function output()
	{
		$output = $this->getFrontend()->dropdown($this->getResults(), $this->getCount(), $this->query, $this->category_id);

		echo $output;
	}
}
