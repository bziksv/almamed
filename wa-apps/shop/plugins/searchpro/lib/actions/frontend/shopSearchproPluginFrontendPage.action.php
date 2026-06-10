<?php

class shopSearchproPluginFrontendPageAction extends shopFrontendAction
{
	public $query;
	public $category_id;
	public $id;

	private $finder_params;
	private $finder;
	private $filters;
	private $products;
	private $empty_collection;
	private $empty_products;

	protected $action;
	protected $parser;
	protected $plugin;
	protected $env;
	protected $frontend;
	protected $util;
	protected $query_storage;
	protected $settings_storage;
	protected $results_route_url;
	protected $encoded_query;
	protected $category_results_route_url;
	protected $query_string;
	protected $categories;
	protected $is_empty = false;
	protected $default_action_params = array();
	protected $category_routes_model;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->query = shopSearchproPluginHelper::prepareQuery(waRequest::param('query'));
		waRequest::setParam('query', $this->query);
		$this->category_id = waRequest::param('category_id', 0, 'int');
		$this->id = waRequest::post('shop_searchpro_id', '');

		waRequest::setParam('shop_searchpro_debug', 1);
	}

	protected function getEnv()
	{
		if(!isset($this->env)) {
			$this->env = new shopSearchproEnv();
		}

		return $this->env;
	}

	protected function getFrontend()
	{
		if(!isset($this->frontend)) {
			$this->frontend = new shopSearchproFrontend(null, $this->getSettings(), $this->getEnv());
		}

		return $this->frontend;
	}

	protected function getResultsRouteUrl()
	{
		if(!isset($this->results_route_url)) {
			$this->results_route_url = wa()->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'));
		}

		return $this->results_route_url;
	}

	protected function getQueryString()
	{
		if(!isset($this->query_string)) {
			$params = waRequest::get();
			$delete = array('page', 'sort', 'order');
			foreach($delete as $k) {
				if(isset($params[$k])) {
					unset($params[$k]);
				}
			}

			$query = $this->getUtil()->buildQuery($params);
			$this->query_string = $query ? ('?' . $query) : '';
		}

		return $this->query_string;
	}

	protected function getCategoryResultsRouteUrl()
	{
		if(!isset($this->category_results_route_url)) {
			$this->category_results_route_url = $this->getResultsRouteUrl() . '/%CATEGORY_ID%/' . $this->getEncodedQuery() . '/' . $this->getQueryString();
		}

		return $this->category_results_route_url;
	}

	protected function getAllResultsUrl()
	{
		$all_results_url = $this->getResultsRouteUrl() . '/' . $this->getEncodedQuery() . '/';

		return $all_results_url;
	}

	protected function getEncodedQuery()
	{
		if(!isset($this->encoded_query)) {
			$this->encoded_query = str_replace('%2F', '', urlencode($this->query));
		}

		return $this->encoded_query;
	}

	protected function getQueryStorage()
	{
		if(!isset($this->query_storage)) {
			$this->query_storage = new shopSearchproQueryStorage();
		}

		return $this->query_storage;
	}

	protected function getUtil()
	{
		if(!isset($this->util)) {
			$this->util = new shopSearchproUtil();
		}

		return $this->util;
	}

	protected function getSettingsStorage()
	{
		if(!isset($this->settings_storage)) {
			$this->settings_storage = new shopSearchproSettingsStorage($this->getEnv());
		}

		return $this->settings_storage;
	}

	protected function getSettings($name = null)
	{
		return $this->getSettingsStorage()->getSettings($name);
	}

	protected function includeAssets($assets)
	{
		$assets = $this->getFrontend()->getAssets($assets, true);

		rsort($assets['js']);
		foreach($assets['js'] as $asset) {
			$this->getResponse()->addJs($asset['url']);
		}

		rsort($assets['css']);
		foreach($assets['css'] as $asset) {
			$this->getResponse()->addCss($asset['url']);
		}
	}

	public function preExecute()
	{
		$this->getResponse()->setTitle("{$this->query} — {$this->getStoreName()}");
	}

	protected function getFilterStatus()
	{
		$filter_status = (bool) $this->getSettings('page_filter_status');

		return $filter_status;
	}

	protected function getCategoryStatus()
	{
		$category_status = (bool) $this->getSettings('page_category_status');

		return $category_status;
	}

	protected function getCategoryMode()
	{
		if(!$this->getFilterStatus()) {
			return 'inline';
		}

		$category_mode = $this->getSettings('page_category_mode');

		return $category_mode;
	}

	protected function getCategoryRoutesModel()
	{
		if(!isset($this->category_routes_model)) {
			$this->category_routes_model = new shopCategoryRoutesModel();
		}

		return $this->category_routes_model;
	}

	protected function getCategoryInlineModeStyle()
	{
		$category_mode = $this->getCategoryMode();

		if($category_mode === 'inline') {
			$category_inline_mode_style = $this->getSettings('page_category_inline_mode_style');

			return $category_inline_mode_style;
		}

		return null;
	}

	protected function getCategoryImage()
	{
		$category_image = $this->getSettings('page_category_image');

		return $category_image;
	}

	protected function getSortStatus()
	{
		$sort_status = (bool) $this->getSettings('page_sort_status');

		return $sort_status;
	}

	protected function isCustom()
	{
		$is_custom = $this->getFilterStatus() || $this->getCategoryStatus() || $this->getSortStatus();

		return $is_custom;
	}

	protected function isInCategory()
	{
		$is_in_category = $this->category_id !== 0;

		return $is_in_category;
	}

	public function execute()
	{
		$this->workupQuery();

		$products = $this->getProducts();

		if($products->isEmpty() && !$this->isAjax()) {
			$this->executeEmpty();
		} else {
			$this->executeDefault();
		}

		$this->postExecute();
	}

	public function postExecute()
	{
		$products = $this->getProducts();

		$this->view->assign('is_in_category', $this->isInCategory());
		if($this->isInCategory()) {
			$this->view->assign('category_id', $this->category_id);
			$this->view->assign('all_results_url', $this->getAllResultsUrl());
		}

		if(!$products->isEmpty()) {
			$vars = $this->view->getVars();
			$content = $this->getFrontend()->page($vars);

			$this->view->assign('content', $content);
		}
	}

	protected function workupQuery()
	{
		$rules = $this->getSettings('detector_rules');
		$env = $this->getEnv();

		$detector = new shopSearchproDetector($env, $rules);

		$data = array(
			'query' => $this->query
		);

		$action = $detector->getAction($data);

		if ($action instanceof shopSearchproDetectorAction) {
			$action->execute();
		}
	}

	protected function cssAssets()
	{
		$css = array();
		if($this->getSettings('design_custom_fonts_status')) {
			$css[] = 'fonts';
		}
		$css[] = 'page';

		return $css;
	}

	public function executeEmpty()
	{
		$this->initEmpty();

		$assets = array(
			'css' => $this->cssAssets()
		);
		$this->includeAssets($assets);

		$this->getQueryStorage()->save($this->query, $this->category_id, 0);

		$collection = $this->getEmptyCollection();

		$page = $this->getContent();
		$vars = array(
			'content' => '',
			'products_collection' => $collection,
			'products' => $collection->getProducts()
		);

		$count = $this->getAction()->getCount();
		if($count !== 0) {
			$vars['content'] = $page->getContent();
		}

		$content = $this->getFrontend()->emptyPage($this->query, $this->category_id, $vars);

		$this->view->assign('content', $content);

		$this->setTemplate($this->getEnv()->getPluginPath() . 'templates/actions/frontend/FrontendEmptyPage.html');
	}

	public function executeDefault()
	{
		$assets = array(
			'js' => array('page'),
			'css' => $this->cssAssets()
		);
		$this->includeAssets($assets);

		$products = $this->getProducts();
		$this->saveQuery($products->getCount());

		if($this->getFilterStatus()) {
			$filter_position = $this->getSettings('design_filter_position');

			if($filter_position === 'theme') {
				$filters_array = $this->getFilters();
				$this->getAction()->setFilters($filters_array);
			}
		}

		$page = $this->getContent();

		if(empty($this->id)) {
			$this->id = uniqid();
		}

		$id = "searchpro-page-wrapper-{$this->id}";
		$this->view->assign('id', $id);

		$this->view->assign('query', $this->query);

		$this->view->assign('category_status', $this->getCategoryStatus());
		$this->view->assign('category_mode', $this->getCategoryMode());
		$this->view->assign('category_inline_mode_style', $this->getCategoryInlineModeStyle());
		$this->view->assign('sort_status', $this->getSortStatus());

		$this->view->assign('is_initial', false);
		$this->view->assign('page_h1', $page->getH1());
		$this->view->assign('page_content', $page->getContent());

		$products_count = $this->getAction()->getCount();
		$this->view->assign('products_count', $products_count);
		$all_products_count = $products->getCount();
		$this->view->assign('all_products_count', $all_products_count);

		if(!$products->isEmpty()) {
			$this->workupCategories();
			$this->workupFilters($id);
		}
	}

	protected function saveQuery($count = null)
	{
		$this->getQueryStorage()->save($this->query, $this->category_id, $count);
		$this->getEnv()->pushSearchHistory($this->query);
	}

	protected function workupFilters($id)
	{
		if(!$this->getFilterStatus()) {
			return;
		}

		$filters_array = $this->getFilters();

		$params = array(
			'page_id' => $id,
			'category_status' => $this->getCategoryStatus(),
			'category_mode' => $this->getCategoryMode(),
			'category_inline_mode_style' => $this->getCategoryInlineModeStyle(),
			'categories' => $this->getCategoryStatus() ? $this->getCategories() : array(),
			'is_in_category' => $this->isInCategory(),
			'category_id' => $this->category_id
		);

		$vars = $this->view->getVars();
		$vars = array_merge($vars, $params);
		$filters = $this->getFrontend()->filters($filters_array, $vars);

		$filter_position = $this->getSettings('design_filter_position');
		$this->view->assign('filter_position', $filter_position);
		$this->view->assign('filters', $filters);
		$this->view->assign('filters_array', $filters_array);
	}

	protected function getCategorySearchUrl($category_id)
	{
		$category_results_route_url = $this->getCategoryResultsRouteUrl();
		$category_search_url = str_replace('%CATEGORY_ID%', $category_id, $category_results_route_url);

		return $category_search_url;
	}

	protected function workupCategories()
	{
		$category_status = $this->getCategoryStatus();

		$categories = array();
		$categories_count = 0;
		if($category_status) {
			$max_category_count = (int) $this->getSettings('page_category_max_count');

			$categories = $this->getCategories();

			$categories_count = count($categories);

			if($max_category_count && $categories_count > $max_category_count) {
				$categories = array_slice($categories, 0, $max_category_count);

				$this->setCategories($categories);
			}
		}

		$this->view->assign('categories', $categories);
		$this->view->assign('categories_count', $categories_count);
	}

	protected function setCategories($categories)
	{
		$this->categories = $categories;
	}

	protected function getCategories()
	{
		if(!isset($this->categories)) {
			$collection = $this->getAction()->getFilteredCollection();

			$hash_params = $collection->getHash();
			$product_ids = null;
			if (is_array($hash_params) && ifset($hash_params, '0', null) === 'id')
			{
				$product_ids = explode(',', $hash_params[1]);
				$search_result_count = count($product_ids);
			}
			else
			{
				$search_result_count = $collection->count();
			}

			$categories = [];
			if ($search_result_count > 600)
			{
				$categories_search_collection = null;
				if (is_array($product_ids) && count($product_ids) > 0)
				{
					$categories_search_collection = new shopProductsCollection(array_slice($product_ids, 0, 600));
					$categories_search_collection->filters(waRequest::get());
				}
			}
			else
			{
				$categories_search_collection = $collection;
			}


			if ($categories_search_collection)
			{
				// todo поправить: если товаров очень много, то getCollectionCategories может базу положить
				$categories = $this->getUtil()->getCollectionCategories($categories_search_collection);
			}

			$is_category_image = false;
			$category_inline_mode_style = $this->getCategoryInlineModeStyle();
			if($category_inline_mode_style) {
				$category_image = $this->getCategoryImage();
				$category_image_view = $this->getFrontend()->createView();

				$is_category_image = true;
			}

			$current_storefront = $this->getEnv()->getCurrentStorefront();
			$existing_category_names = array();
			foreach($categories as $key => &$category) {
				if(!$category) {
					unset($categories[$key]);
					continue;
				}

				$routes = $this->getCategoryRoutesModel()->getRoutes($category['id']);

				if(!empty($routes) && !in_array($current_storefront, $routes)) {
					unset($categories[$key]);
					continue;
				}

				if(array_key_exists($category['name'], $existing_category_names)) {
					$existing_category_names[$category['name']][] = $category['id'];
				} else {
					$existing_category_names[$category['name']] = array($category['id']);
				}

				$category['existing_name'] = false;
				$category['search_url'] = $this->getCategorySearchUrl($category['id']);

				if($is_category_image) {
					$category_image_view->assign('category', $category);

					$category['image'] = @$category_image_view->fetch("string:$category_image");
				}
			}

			foreach($existing_category_names as $name => $ids) {
				if(count($ids) > 1)
					foreach($ids as $id)
						$categories[$id]['existing_name'] = true;
			}

			sort($categories);

			$this->categories = $categories;
		}

		return $this->categories;
	}

	protected function getFinderParams()
	{
		if(!isset($this->finder_params)) {
			$this->finder_params = array(
				'mode' => $this->getSettings('search_mode'),
				'slice_query' => $this->getSettings('search_slice_query'),
				'rest_words' => $this->getSettings('search_rest_words'),
				'word_forms' => $this->getSettings('search_word_forms'),
				'form_break_symbols' => $this->getSettings('search_form_break_symbols'),
				'form_numbers' => $this->getSettings('search_form_numbers'),
				'form_strnum' => $this->getSettings('search_form_strnum'),
				'form_ignore_numstart' => $this->getSettings('search_form_ignore_numstart'),
				'form_min_length' => $this->getSettings('search_form_min_length'),
				'cache_type' => 'page',
				'cache_actuality' => $this->getSettings('page_results_cache'),
				'category_id' => $this->category_id,
				'match_status' => $this->getSettings('match_status'),
				'corrector_status' => $this->getSettings('corrector_status'),
				'counts' => array(
					'products' => array(
						'min' => $this->getSettings('page_products_min_count')
					),
				),
				'fields' => array(
					'products' => array(
						'pages' => $this->getSettings('page_products_pages_status'),
						'seopage_plugin' => $this->getSettings('page_products_seopage_plugin_status')
					)
				),
				'translate_status' => $this->getSettings('translate_status'),
				'grams_status' => $this->getSettings('grams_status'),
				'grams_mode' => $this->getSettings('grams_mode'),
				'keyboard_layout_status' => $this->getSettings('keyboard_layout_status'),
				'keyboard_layout_mode' => $this->getSettings('keyboard_layout_mode'),
				'combine_status' => $this->getSettings('combine_status'),
			);
		}

		return $this->finder_params;
	}

	/**
	 * @return shopSearchproFinder
	 */
	protected function getFinder()
	{
		if(!isset($this->finder)) {
			$params = $this->getFinderParams();
			$this->finder = new shopSearchproFinder($params);
		}

		return $this->finder;
	}

	/**
	 * @return shopSearchproResult
	 * @throws waException
	 */
	protected function getProducts()
	{
		if(!isset($this->products)) {
			$this->products = $this->getFinder()->find('products', $this->query);
		}

		return $this->products;
	}

	/**
	 * @return shopProductsCollection
	 * @throws waException
	 */
	protected function getCollection()
	{
		return $this->getProducts()->getInitialCollection();
	}

	protected function getEmptyCollection()
	{
		if(!isset($this->empty_collection)) {
			if($this->getSettings('page_empty_products_status')) {
				$set_id = $this->getSettings('page_empty_products_set');

				if($set_id) {
					$hash = "set/$set_id";
				} else {
					$hash = array();
				}
			} else {
				$hash = array();
			}

			$this->empty_collection = new shopProductsCollection($hash);
		}

		return $this->empty_collection;
	}

	/**
	 * @return shopSearchproResult
	 */
	protected function getEmptyProducts()
	{
		if(!isset($this->empty_products)) {
			$max_length = $this->getSettings('page_empty_products_max_length');

			if($max_length === '') {
				$max_length = null;
			} else {
				$max_length = (int) $max_length;
			}

			$initial_products = $this->getEmptyCollection()->getProducts('*', 0, $max_length);
			$this->empty_products = new shopSearchproResult($initial_products);
		}

		return $this->empty_products;
	}

	protected function getActionParams()
	{
		$is_empty = $this->isEmpty();

		if ($is_empty) {
			$products = $this->getEmptyProducts();
		} else {
			$products = $this->getProducts();
		}

		$query = $this->query;

		$params = compact('products', 'query', 'categories', 'is_empty');
		$params = array_merge($this->default_action_params , $params);

		if ($is_empty)
		{
			$limit = $this->getSettings('page_empty_products_max_length');

			if(wa_is_int($limit) && $limit > 0)
			{
				$params['limit'] = $limit;
			}
		}

		return $params;
	}

	/**
	 * @return void
	 */
	protected function initEmpty()
	{
		$this->is_empty = true;
		$this->default_action_params['limit'] = 5;
	}

	/**
	 * @return bool
	 */
	protected function isEmpty()
	{
		return $this->is_empty;
	}

	/**
	 * @return bool
	 */
	protected function isAjax()
	{
		return waRequest::isXMLHttpRequest();
	}

	/**
	 * @return shopSearchproPluginFrontendPageSearchAction
	 */
	protected function getAction()
	{
		if(!isset($this->action)) {
			$params = $this->getActionParams();
			$this->action = new shopSearchproPluginFrontendPageSearchAction($params);
		}

		return $this->action;
	}

	/**
	 * @return shopSearchproPluginFrontendContentParser
	 */
	protected function getParser()
	{
		if(!isset($this->parser)) {
			$this->parser = new shopSearchproPluginFrontendContentParser();
		}

		return $this->parser;
	}

	/**
	 * @param string $content
	 * @return shopSearchproPluginFrontendContentParserResult
	 */
	protected function parseContent($content)
	{
		return $this->getParser()->parse($content);
	}

	/**
	 * @return string
	 */
	protected function getInitialContent()
	{
		$initial_content = $this->getAction()->display(false);

		return $initial_content;
	}

	/**
	 * @param string|null $initial_content
	 * @return shopSearchproPluginFrontendContentParserResult
	 */
	protected function getContent($initial_content = null)
	{
		if($initial_content === null) {
			$initial_content = $this->getInitialContent();
		}

		$parsed_content = $this->parseContent($initial_content);

		return $parsed_content;
	}

	private function getFilters()
	{
		if(!isset($this->filters)) {
			$products = $this->getProducts();

			$is_selectable_features = (bool) $this->getSettings('page_filter_selectable_status');
			$disabled_features = $this->getSettings('page_filter_disabled_features');

			$filters_instance = new shopSearchproFilters($products->getIds());
			$features_values = $filters_instance->getFeaturesValues(true, $is_selectable_features, $disabled_features);

			$price_status = $this->getSettings('page_filter_price_status');
			if($price_status) {
				$price_range = $products->getPriceRange();
				$features_values['price'] = $price_range;
			}

			$this->filters = $this->getFiltersByFeaturesValues($features_values);
		}

		return $this->filters;
	}

	/**
	 * @param array $features_values
	 * @return array
	 */
	private function getFiltersByFeaturesValues($features_values)
	{
		$features_sort = $this->getSettings('page_filter_features_sort');

		$feature_model = new shopFeatureModel();

		$price_range = ifset($features_values, 'price', null);
		if($price_range !== null) {
			unset($features_values['price']);
		}

		$feature_ids = array_keys($features_values);
		$feature_values_ids = array();
		foreach($features_values as $feature_values) {
			$feature_values_ids = array_merge($feature_values_ids, $feature_values);
		}

		$features = $feature_model->getById($feature_ids);
		$is_shop_script_8 = $this->getEnv()->isShopScript8();

		if($features) {
			$all = $is_shop_script_8 ? $features_values : true;
			$features = $feature_model->getValues($features, $all);
		}

		if($price_range !== null) {
			$features_values = array('price' => $price_range) + $features_values;
		}

		$filters = array();
		foreach($features_values as $fid => $fvalues) {
			$is_boolean = false;
			if($fid === 'price') {
				$range = $fvalues;
				if($range['min'] !== $range['max']) {
					$filters['price'] = array(
						'min' => shop_currency($range['min'], null, null, false),
						'max' => shop_currency($range['max'], null, null, false),
					);
				}
			} elseif(isset($features[$fid])) {
				if($features[$fid]['type'] === 'boolean') {
					$is_boolean = true;
					$features[$fid]['values'] = array(true);
				} else {
					if(count($fvalues) === 1) {
						continue;
					}
				}

				$filters[$fid] = $features[$fid];
				$min = $max = $unit = null;
				foreach($filters[$fid]['values'] as $v_id => $v) {
					if(!in_array($v_id, $fvalues) && !$is_boolean) {
						unset($filters[$fid]['values'][$v_id]);
					} else {
						if($v instanceof shopRangeValue) {
							$begin = $this->getFeatureValue($v->begin);
							if(is_numeric($begin) && ($min === null || (float)$begin < (float)$min)) {
								$min = $begin;
							}
							$end = $this->getFeatureValue($v->end);
							if(is_numeric($end) && ($max === null || (float)$end > (float)$max)) {
								$max = $end;
								if($v->end instanceof shopDimensionValue) {
									$unit = $v->end->unit;
								}
							}
						} else {
							$tmp_v = $this->getFeatureValue($v);
							if($min === null || $tmp_v < $min) {
								$min = $tmp_v;
							}
							if($max === null || $tmp_v > $max) {
								$max = $tmp_v;
								if($v instanceof shopDimensionValue) {
									$unit = $v->unit;
								}
							}
						}
					}
				}

				if(!$filters[$fid]['selectable'] && ($filters[$fid]['type'] == 'double' ||
						substr($filters[$fid]['type'], 0, 6) == 'range.' ||
						substr($filters[$fid]['type'], 0, 10) == 'dimension.')
				) {
					if($min == $max) {
						unset($filters[$fid]);
					} else {
						$type = preg_replace('/^[^\.]*\./', '', $filters[$fid]['type']);
						if($type != 'double') {
							$filters[$fid]['base_unit'] = shopDimension::getBaseUnit($type);
							$filters[$fid]['unit'] = shopDimension::getUnit($type, $unit);
							if($filters[$fid]['base_unit']['value'] != $filters[$fid]['unit']['value']) {
								$dimension = shopDimension::getInstance();
								$min = $dimension->convert($min, $type, $filters[$fid]['unit']['value']);
								$max = $dimension->convert($max, $type, $filters[$fid]['unit']['value']);
							}
						}
						$filters[$fid]['min'] = $min;
						$filters[$fid]['max'] = $max;
					}
				}
			}
		}

		if($filters) {
			foreach($filters as $field => $filter) {
				if(isset($filters[$field]['values']) && (!count($filters[$field]['values']))) {
					unset($filters[$field]);
				}
			}

			$mem_filters = array();
			if(isset($filters['price'])) {
				$mem_filters['price'] = $filters['price'];
				unset($filters['price']);
			}

			if (!empty($features_sort)) {
				$ordered_filters = array();

				foreach($features_sort as $feature_id) {
					if(array_key_exists($feature_id, $filters)) {
						$ordered_filters[$feature_id] = $filters[$feature_id];
					}
				}
			} else {
				$ordered_filters = $filters;
			}

			$filters = $mem_filters + $ordered_filters;

			return $filters;
		}

		return array();
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function getFeatureValue($value)
	{
		if($value instanceof shopDimensionValue) {
			return $value->value_base_unit;
		}

		if(is_object($value) && property_exists($value, 'value')) {
			return $value->value;
		}

		return $value;
	}
}
