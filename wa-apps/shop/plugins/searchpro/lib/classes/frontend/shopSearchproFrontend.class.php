<?php

class shopSearchproFrontend
{
	public $assigns = array();
	private $view;

	protected static $env;

	protected $plugin;
	protected $settings = array();
	protected $query_model;
	protected $templates_instance;
	protected $currency;

	protected $is_disabled_assets = array(
		'init' => false
	);

	/**
	 * @param waView|null $view
	 * @param array|null $settings
	 * @param shopSearchproEnv|null $env
	 * @param shopSearchproPlugin|null $plugin
	 */
	public function __construct($view = null, $settings = null, $env = null, $plugin = null)
	{
		if($view)
			$this->view = $view;

		if($plugin)
			$this->plugin = $plugin;

		if($settings !== null)
			$this->settings = $settings;
		else
			$this->settings = $this->getPlugin()->getSettings();

		if($env !== null && $env instanceof shopSearchproEnv)
			self::$env = $env;
	}

	public function disableAssets($assets)
	{
		foreach($assets as $asset)
			$this->is_disabled_assets[$asset] = true;
	}

	public function enableAssets($assets)
	{
		foreach($assets as $asset)
			$this->is_disabled_assets[$asset] = false;
	}

	/**
	 * Возвращает URL подключаемых скриптов и таблиц стилей
	 * @param array $assets
	 * @param bool $absolute
	 * @return array $output_assets
	 */
	public function getAssets($assets, $absolute = false)
	{
		$output_assets = array(
			'js' => array(),
			'css' => array()
		);
		$version = $this->getPlugin()->getVersion();

		if(isset($assets['css'])) {
			foreach($assets['css'] as $asset) {
				$output_assets['css'][$asset] = array(
					'type' => 'css',
					'asset' => $asset,
					'url' => $this->getTemplatesInstance()->get('stylesheet_' . strtolower($asset), 'url') . '?v' . $version
				);

				$custom_stylesheet_url = shopSearchproStylesheet::getOutputUrl($this->getCurrentTheme(), $asset);
				if($custom_stylesheet_url) {
					$output_assets['css']["custom.$asset"] = array(
						'type' => 'css',
						'url' => $custom_stylesheet_url
					);
				};
			}
		}

		if(isset($assets['js'])) {
			foreach($assets['js'] as $asset) {
				$output_assets['js'][$asset] = array(
					'type' => 'js',
					'asset' => $asset,
					'url' => $this->getPlugin()->getPluginStaticUrl($absolute) . "js/frontend.$asset.js?v$version"
				);
			}
		}

		if(empty($this->is_disabled_assets['init']) && (!waRequest::isXMLHttpRequest() || waRequest::get('shop_searchpro_init'))) {
			$output_assets['init'] = array(
				'type' => 'js',
				'url' => $this->getEnv()->getRouteUrl('shop/frontend/config/', array(
						'plugin' => 'searchpro'
					), true) . '?v' . $version
			);
		}

		return $output_assets;
	}

	protected function getTemplatesInstance()
	{
		if(!isset($this->templates_instance)) {
			$this->templates_instance = new shopSearchproTemplates();
		}

		return $this->templates_instance;
	}

	protected function getQueryModel()
	{
		if(!isset($this->query_model)) {
			$this->query_model = new shopSearchproQueryModel();
		}

		return $this->query_model;
	}

	protected static function getEnv()
	{
		if(!isset(self::$env)) {
			self::$env = new shopSearchproEnv();
		}

		return self::$env;
	}

	/**
	 * @return string
	 */
	protected function getCurrentTheme()
	{
		if(!isset($this->theme_id)) {
			$this->theme_id = $this->getEnv()->getCurrentTheme();
		}

		return $this->theme_id;
	}

	protected function getCurrency()
	{
		if(!isset($this->currency)) {
			$currency_id = $this->getEnv()->getConfig()->getCurrency(false);
			$this->currency = waCurrency::getInfo($currency_id);
		}

		return $this->currency;
	}

	protected function getPlugin()
	{
		if(!isset($this->plugin)) {
			$this->plugin = shopSearchproPlugin::getInstance('frontend');
		}

		return $this->plugin;
	}

	protected function getPluginPath()
	{
		return wa()->getAppPath('plugins/searchpro/', 'shop');
	}

	protected function getTemplatePath($template)
	{
		return $this->getPluginPath() . "templates/actions/frontend/Frontend$template.html";
	}

	public function assign($name, $value, $directly = false)
	{
		if($directly) {
			$this->view->assign($name, $value);
		} else {
			$this->getView()->assign($name, $value);
		}

		$this->assigns[$name] = true;
	}

	public function getTheme()
	{
		if(!isset($this->theme)) {
			$this->theme = new waTheme(waRequest::getTheme());
		}

		return $this->theme;
	}

	private function registerView()
	{
		if(!isset($this->view))
			$this->view = $this->createView();

		if(empty($this->assigns['plugin_path'])) {
			$this->assign('plugin_path', $this->getPluginPath(), true);
		}

		if(empty($this->assigns['plugin_url'])) {
			$this->assign('plugin_url', $this->getEnv()->getPluginUrl(), true);
		}

		if(empty($this->assigns['_theme'])) {
			$this->view->setThemeTemplate($this->getTheme(), 'index.html');
			$this->assigns['_theme'] = true;
			$this->assign('current_theme_id', $this->getCurrentTheme(), true);
		}

		if(empty($this->assigns['_register'])) {
			$templates_instance = new shopSearchproTemplates();
			$templates_instance->register($this->view);
			$this->assigns['_register'] = true;
		}
	}

	private function getResponse()
	{
		return wa()->getResponse();
	}

	public function createView()
	{
		return new waSmarty3View(wa());
	}

	public function getView()
	{
		$this->registerView();

		return $this->view;
	}

	public function getSettings($name = null)
	{
		return $this->getEnv()->getActiveSettings($this->settings, $name);
	}

	public function dropdown($results = array(), $count = null, $query = null, $category_id = 0)
	{
		$entities_count = count($results);
		$empty_entities_count = 0;

		$results_route_url = $this->getEnv()->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'));
		$encoded_query = shopSearchproUtil::encodeQueryUrl($query);

		foreach($results as $group => &$entities) {
			if(empty($entities)) {
				$empty_entities_count++;
				continue;
			}

			$existing_entity_names = array();
			foreach($entities as $entity_key => &$entity) {
				if($group === 'categories') {
					$entity['category_results_url'] = "{$results_route_url}/{$entity['id']}/{$encoded_query}/";
				}

				if(array_key_exists($entity['name'], $existing_entity_names)) {
					$existing_entity_names[$entity['name']][] = $entity_key;
				} else {
					$existing_entity_names[$entity['name']] = array($entity_key);
				}

				$entity['existing_name'] = false;

				if(isset($entity['query'])) {
					$entity['regex'] = array();

					$words = explode(' ', $entity['query']);
					foreach($words as $word) {
						$regex_word = preg_quote(trim($word), '/');

						if(preg_match('/^[а-яё]+$/iu', $regex_word) && mb_strlen($regex_word) >= 4) {
							/**
							 * Последние 2 (или 1 если строка из 4 символов) символа в русском языке выделяем
							 */
							$query_regex = mb_substr($regex_word, 0, mb_strlen($word) == 4 ? -1 : -2);
							$query_regex .= mb_strlen($regex_word) == 4 ? '.' : '..';
						} elseif(preg_match('/^[a-z]+$/', $regex_word) && strlen($regex_word) >= 4) {
							/**
							 * Последний символ в английском языке может быть так же "s" или "es"
							 */
							$query_regex = $regex_word . '(?:es|s)?';
						} else {
							$query_regex = $regex_word;
						}

						$entity['regex'][] = $query_regex;
					}

					$entity['regex'] = implode('|', $entity['regex']);
				}
			}

			foreach($existing_entity_names as $name => $ids) {
				if(count($ids) > 1)
					foreach($ids as $id)
						$entities[$id]['existing_name'] = true;
			}
		}

		if($empty_entities_count === $entities_count)
			return '';

		$category_id = intval($category_id);

		$vars['query'] = $query;
		$vars['encoded_query'] = $encoded_query;
		$vars['category_id'] = $category_id;

		$results_url = $results_route_url;
		if($category_id) {
			$results_url .= "/{$category_id}";
		}
		$results_url .= "/{$encoded_query}/";
		$vars['results_url'] = $results_url;

		$vars['results'] = $results;
		$vars['count'] = $count;

		$products_image_status = $this->getSettings('dropdown_products_image_status');
		$products_price_status = $this->getSettings('dropdown_products_price_status');
		$products_summary_status = $this->getSettings('dropdown_products_summary_status');

		$vars['products_image_status'] = $products_image_status;
		$vars['products_price_status'] = $products_price_status;
		$vars['products_summary_status'] = $products_summary_status;

		$vars['c'] = $this->getCurrency();

		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_searchpro:{$current_theme_id}/dropdown");
	}

	public function page(array $vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_searchpro:{$current_theme_id}/page");
	}

	public function emptyPage($query, $category_id = 0, array $vars = array())
	{
		$category_id = (int) $category_id;

		$vars['query'] = $query;
		$vars['category_id'] = $category_id;

		$is_in_category = $category_id !== 0;
		$vars['is_in_category'] = $is_in_category;

		$popular = null;
		$is_popular = (bool) $this->getSettings('page_empty_popular_status');
		if($is_popular) {
			$results_route_url = $this->getEnv()->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'));

			$popular_max_count = (int) $this->getSettings('page_empty_popular_max_length');
			$popular = $this->getQueryModel()->getVisible($popular_max_count);

			foreach($popular as &$entity) {
				$url = $results_route_url;
				$encoded_query = urlencode($entity['query']);
				if($entity['category_id']) {
					$url .= "/{$entity['category_id']}";
				}
				$url .= "/{$encoded_query}/";

				$entity['name'] = $entity['query'];
				$entity['url'] = $url;
			}
		}

		$vars['popular'] = $popular;

		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_searchpro:{$current_theme_id}/empty_page");
	}

	public function filters(array $filters, array $vars = array())
	{
		$ajax = (bool) $this->getSettings('design_filter_ajax_status');

		$view = $this->getView();
		$view->assign($vars);
		$view->assign('filters', $filters);
		$view->assign('ajax', $ajax);
		$view->assign('c', $this->getCurrency());

		$id = uniqid('searchpro-filters-wrapper-');
		$view->assign('id', $id);

		$path = wa()->getAppPath('plugins/searchpro/templates/actions/frontend/FrontendFilters.html', 'shop');

		return $view->fetch($path);
	}

	public function helperDropdown($params, $is_example = false)
	{
		$results_route_url = $this->getEnv()->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'));

		$history = array();
		$popular = array();

		$view = $this->getView();

		if($is_example) {
			$history = array(array(
				'url' => $results_route_url . '/%QUERY%/',
				'query' => ''
			));
		} else {
			$history_status = ifset($params, 'history', 'status', false);

			if($history_status) {
				$history_max_count = ifset($params, 'history', 'max', 0);
				$history = $this->getEnv()->getSearchHistory(true);

				$history = array_slice($history, 0, $history_max_count);

				foreach($history as &$query) {
					$encoded_query = shopSearchproUtil::encodeQueryUrl($query);
					$url = $results_route_url . "/{$encoded_query}/";

					$query = array(
						'url' => $url,
						'query' => $query
					);
				}
			}
		}

		$popular_status = ifset($params, 'popular', 'status', false);

		if($popular_status) {
			$popular_max_count = ifset($params, 'popular', 'max', 0);

			$popular = $this->getQueryModel()->getVisible($popular_max_count);

			foreach($popular as &$entity) {
				$url = $results_route_url;
				$encoded_query = shopSearchproUtil::encodeQueryUrl($entity['query']);
				if($entity['category_id']) {
					$url .= "/{$entity['category_id']}";
				}
				$url .= "/{$encoded_query}/";

				$entity['name'] = $entity['query'];
				$entity['url'] = $url;
			}
		}

		$delete_status = (bool) $this->getSettings('dropdown_history_delete_status');
		$view->assign('history_delete_status', $delete_status);

		if(empty($history) && empty($popular)) {
			return '';
		}

		$view->assign('history', $history);
		$view->assign('popular', $popular);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_searchpro:{$current_theme_id}/helper_dropdown");
	}

	public function field($params = array())
	{
		if(!$this->getSettings('status')) {
			return '';
		}

		$assets = array();
		if(empty($this->is_disabled_assets['field'])) {
			$css = array();
			if($this->getSettings('design_custom_fonts_status')) {
				$css[] = 'fonts';
			}
			$css[] = 'field';

			$assets = array(
				'js' => array('field'),
				'css' => empty($params['no_css']) ? $css : array()
			);
		}

		$category_filter_status = (bool) $this->getSettings('category_filter_status');
		$category_filter_deep = (int) $this->getSettings('category_filter_deep');

		$vars['category_filter_status'] = $category_filter_status;
		$vars['category_filter_deep'] = $category_filter_deep;

		$vars['query'] = '';
		if(waRequest::param('query')) {
			$vars['query'] = waRequest::param('query');
		}

		if(waRequest::param('action') === 'category') {
			$url_field = waRequest::param('url_type') == 1 ? 'url' : 'full_url';
			$category_url = waRequest::param('category_url');

			if($category_url) {
				$category_model = new shopCategoryModel();
				$category = $category_model->getByField($url_field, $category_url);

				$vars['selected_category'] = $category;
				$vars['is_in_category'] = true;
			}
		} else {
			$vars['is_in_category'] = false;
		}

		if($category_filter_status) {
			$route = $this->getEnv()->getCurrentStorefront();
			$source_categories = $this->getEnv()->getCategoryModel()->getTree(0, $category_filter_deep, true, $route);
			$stack = array();
			$categories = array();
			foreach($source_categories as $key => $category) {
				$is_hidden_parent = $category['parent_id'] && $category['id'] && !isset($source_categories[$category['parent_id']]);

				if($category['status'] === '0' || $is_hidden_parent) {
					continue;
				}

				$category['childs'] = array();

				$l = count($stack);

				while($l > 0 && $stack[$l - 1]['depth'] >= $category['depth']) {
					array_pop($stack);
					$l--;
				}

				if ($l == 0) {
					$i = count($categories);
					$categories[$i] = $category;
					$stack[] = &$categories[$i];
				} else {
					$i = count($stack[$l - 1]['childs']);
					$stack[$l - 1]['childs'][$i] = $category;
					$stack[] = &$stack[$l - 1]['childs'][$i];
				}
			}

			$vars['categories'] = $categories;
		}

		$vars['placeholder'] = $this->getSettings('placeholder');
		$vars['button'] = $this->getSettings('button');

		$uniqid = uniqid('searchpro-field-wrapper-');
		$vars['uniqid'] = $uniqid;

		$dropdown_status = (bool) $this->getSettings('dropdown_status');
		$category_status = (bool) $this->getSettings('category_filter_status');
		$dropdown_min_length = (int) $this->getSettings('dropdown_min_length');
		$popular_status = (bool) $this->getSettings('dropdown_popular_is_visible');
		$popular_max_count = (int) $this->getSettings('dropdown_popular_max_count');
		$history_status = (bool) $this->getSettings('dropdown_history_is_visible');
		$history_search_status = (bool) $this->getSettings('dropdown_history_status');
		$history_max_count = (int) $this->getSettings('dropdown_history_max_count');
		$history_cookie_key = shopSearchproEnv::HISTORY_COOKIE_KEY;
		$clear_button_status = (bool) $this->getSettings('clear_button');
		$params = compact('dropdown_status', 'category_status', 'dropdown_min_length', 'history_cookie_key', 'popular_status', 'popular_max_count', 'history_status', 'history_search_status', 'history_max_count', 'clear_button_status');

		if($history_status || $popular_status) {
			$helper_params = array(
				'history' => array(
					'status' => $history_status,
					'max' => $history_max_count
				),
				'popular' => array(
					'status' => $popular_status,
					'max' => $popular_max_count
				)
			);
			$helper_dropdown = $this->helperDropdown($helper_params);
			$helper_dropdown_example = $this->helperDropdown($helper_params, true);

			$params['helper_dropdown'] = array(
				'current' => $helper_dropdown,
				'template' => $helper_dropdown_example
			);
		}

		$vars['params'] = $params;

		return $this->outputFrontend($this->getPluginPath() . 'templates/actions/frontend/FrontendField.html', $assets, $vars, array(
			'class' => 'js-searchpro__field-wrapper',
			'id' => $uniqid
		));
	}

	protected function outputFrontend($content, $assets = array(), $vars = array(), $wrapper = null, $data = array())
	{
		$view = $this->getView();
		$view->assign('assets', $assets);

		$assets_links = $this->getAssets($assets);
		$view->assign('assets_links', $assets_links);

		$view->assign($vars);

		$content = $view->fetch($content);

		$view->assign(compact('content', 'wrapper', 'data'));

		return $view->fetch($this->getTemplatePath('Output'));
	}
}
