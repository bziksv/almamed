<?php

class shopSearchproEnv
{
	const HISTORY_COOKIE_KEY = 'shop_searchpro_search_history';

	protected $routing;
	protected $plugin_url;
	protected $plugin_path;
	protected $storefront_id;
	protected $theme_id;
	protected $themes = array();

	private $systems = array();
	private $models = array();

	/**
	 * @param string $app
	 * @return waSystem
	 */
	protected function getSystem($app = 'shop')
	{
		if(!isset($this->systems[$app])) {
			$this->systems[$app] = wa($app === 'wa' ? null : $app);
		}

		return $this->systems[$app];
	}

	/**
	 * @param string $app
	 * @return string
	 */
	public function getVersion($app = 'shop')
	{
		return $this->getSystem($app)->getVersion();
	}

	/**
	 * @param string $version
	 * @param string $app
	 * @return bool
	 */
	public function compareVersion($version, $app = 'shop')
	{
		$app_version = $this->getVersion($app);

		return $app_version >= $version;
	}

	/**
	 * @return bool
	 */
	public function isShopScript8()
	{
		return $this->compareVersion('8.0');
	}

	/**
	 * @param string $app
	 * @return SystemConfig|waAppConfig
	 */
	public function getConfig($app = 'shop')
	{
		return $this->getSystem($app)->getConfig();
	}

	/**
	 * @param string $model
	 * @param string $app
	 * @return mixed
	 */
	protected function getModel($model, $app = 'shop')
	{
		$key = "$app/$model";

		if(!isset($this->models[$key])) {
			$class_name = sprintf('%s%sModel', $app, ucfirst($model));
			if(class_exists($class_name)) {
				$this->models[$key] = new $class_name();
			}
		}

		return $this->models[$key];
	}

	/**
	 * @return shopProductModel
	 */
	public function getProductModel()
	{
		return $this->getModel('product');
	}

	/**
	 * @return shopProductSkusModel
	 */
	public function getSkusModel()
	{
		return $this->getModel('productSkus');
	}

	/**
	 * @return shopCategoryModel
	 */
	public function getCategoryModel()
	{
		return $this->getModel('category');
	}

	/**
	 * @return waRouting
	 */
	protected function getRouting()
	{
		if(!isset($this->routing)) {
			$this->routing = $this->getSystem()->getRouting();
		}

		return $this->routing;
	}

	public function getRouteUrl($path, $params = array(), $absolute = false, $domain = null, $route = null)
	{
		return $this->getSystem()->getRouteUrl($path, $params, $absolute, $domain, $route);
	}

	/**
	 * @param bool $is_sort
	 * @return array
	 */
	public function getSearchHistory($is_sort = false)
	{
		$history = waRequest::cookie(self::HISTORY_COOKIE_KEY);
		$history = @json_decode($history);

		if(!is_array($history) || empty($history)) {
			return array();
		}

		if($is_sort) {
			krsort($history);
		}

		return $history;
	}

	/**
	 * @param string $query
	 * @return true
	 */
	public function pushSearchHistory($query)
	{
		$history = $this->getSearchHistory();

		if(!in_array($query, $history)) {
			if (count($history) > 19) {
				$history = array_slice($history, 0, 19);
			}

			$history[] = $query;
			$json_history = json_encode($history);

			$this->getSystem()->getResponse()->setCookie(self::HISTORY_COOKIE_KEY, $json_history);
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getPluginUrl()
	{
		if(!isset($this->plugin_url)) {
			$this->plugin_url = $this->getSystem()->getAppStaticUrl('shop', true) . 'plugins/searchpro/';
		}

		return $this->plugin_url;
	}

	/**
	 * @return string
	 */
	public function getPluginPath()
	{
		if(!isset($this->plugin_path)) {
			$this->plugin_path = $this->getSystem()->getAppPath('plugins/searchpro/');
		}

		return $this->plugin_path;
	}

	/**
	 * @param string $theme_id
	 * @return waTheme
	 */
	public function getTheme($theme_id)
	{
		if(!isset($this->themes[$theme_id])) {
			$this->themes[$theme_id] = new waTheme($theme_id, 'shop');
		}

		return $this->themes[$theme_id];
	}

	/**
	 * @param bool $with_instance
	 * @return array
	 */
	public function getThemes($with_instance = false)
	{
		$themes = array();

		$first_theme_id = null;
		foreach ($this->getRouting()->getByApp('shop') as $domain => $routes) {
			foreach ($routes as $route) {
				if(!array_key_exists('theme', $route)) {
					continue;
				}

				$first_theme_id = $route['theme'];

				break 2;
			}
		}

		foreach($this->getSystem()->getThemes('shop') as $theme) {
			$this->themes[$theme->id] = $theme;

			$theme_assoc = array(
				'id' => $theme->id,
				'name' => $theme->getName(),
			);

			if($with_instance) {
				$theme_assoc['instance'] = $theme;
			}

			if ($first_theme_id == $theme->id) {
				array_unshift($themes, $theme_assoc);
			} else {
				$themes[] = $theme_assoc;
			}
		}

		return $themes;
	}

	/**
	 * @return string
	 */
	public function getCurrentTheme()
	{
		return waRequest::getTheme();
	}

	/**
	 * @return array
	 */
	public function getRoutes()
	{
		$domains = $this->getRouting()->getByApp('shop');
		$urls = array();

		foreach($domains as $domain => $routes) {
			foreach($routes as $route) {
				if((!method_exists($this->getRouting(), 'isAlias') || !$this->getRouting()->isAlias($domain)) and isset($route['url'])) {
					$urls[$domain . '/' . $route['url']] = $this->getRouteUrl('shop/frontend', array(), true, $domain, $route['url']);
				}
			}
		}

		return $urls;
	}

	/**
	 * @return array
	 */
	public function getStorefronts()
	{
		$routes = $this->getRoutes();
		return array_keys($routes);
	}

	/**
	 * @return array
	 */
	public function getStorefrontGroups()
	{
		return $this->getModel('searchproStorefrontGroups')->getAll('id');
	}

	/**
	 * @return array
	 */
	public function getStorefrontGroupKeys()
	{
		$groups = $this->getModel('searchproStorefrontGroups')->getAll('id', false, false);
		$keys = array();

		foreach(array_keys($groups) as $group)
			array_push($keys, "group:$group");

		return $keys;
	}

	/**
	 * @return string
	 */
	public function getCurrentStorefront()
	{
		if(!isset($this->storefront_id)) {
			$route = $this->getRouting()->getRoute();

			if($route['app'] === 'shop') {
				$domain = $this->getRouting()->getDomain();
				$url = $domain . '/' . $route['url'];

				$this->storefront_id = $url;
			} else {
				return null;
			}
		}

		return $this->storefront_id;
	}

	/**
	 * @param string|null $storefront_id
	 * @return string
	 */
	public function getStorefrontGroup($storefront_id = null)
	{
		if($storefront_id === null)
			$storefront_id = $this->getCurrentStorefront();

		$storefront_groups = $this->getStorefrontGroups();

		if(!empty($storefront_groups)) {
			foreach($storefront_groups as $storefront_group_id => $storefront_group_storefronts) {
				if(array_search($storefront_id, $storefront_group_storefronts) !== false) {
					return $storefront_group_id;
					break;
				}
			}
		}

		return null;
	}

	public function getActiveSettings($settings, $name = null, $storefront_id = null)
	{
		if($name === null) {
			return $settings;
		} else {
			if(is_string($name) && isset($settings['basic'][$name])) {
				return $settings['basic'][$name];
			} else {
				if(is_string($name) && substr($name, 0, strlen('design_')) === 'design_') {
					$general_value = ifset($settings, 'themes', '*', $name, null);

					return ifset($settings, 'themes', $this->getCurrentTheme(), $name, $general_value);
				}

				if($storefront_id === null) {
					$storefront_id = $this->getCurrentStorefront();
				}

				$storefront_group = $this->getStorefrontGroup($storefront_id);

				return $this->getActiveSettingsValue($settings, $name, $storefront_id, $storefront_group);
			}
		}
	}

	public function getActiveSettingsValue($settings, $name, $storefront_id = null, $storefront_group = null)
	{
		$general_storefront_value = ifset($settings, 'storefronts', '*', $name, null);

		if($storefront_group !== null) {
			$general_storefront_value = ifset($settings, 'storefronts', "group:$storefront_group", $name, $general_storefront_value);
		}

		if(isset($settings['storefronts'][$storefront_id])) {
			return ifset($settings, 'storefronts', $storefront_id, $name, $general_storefront_value);
		} else {
			return $general_storefront_value;
		}
	}

	public function isEnabledSeopagePlugin()
	{
		return $this->isEnabledShopPlugin('seopage');
	}

	public function isEnabledSeoPlugin()
	{
		return $this->isEnabledShopPlugin('seo');
	}

	public function isEnabledSeofilterPlugin()
	{
		return $this->isEnabledShopPlugin('seofilter');
	}

	public function isEnabledBrandPlugin()
	{
		return $this->isEnabledShopPlugin('brand');
	}

	public function isEnabledProductbrandsPlugin()
	{
		return $this->isEnabledShopPlugin('productbrands');
	}

	public function isFrontend()
	{
		return wa()->getEnv() === 'frontend';
	}

	private function isEnabledShopPlugin($plugin)
	{
		$plugin_info = $this->getSystem()->getConfig()->getPluginInfo($plugin);

		return !empty($plugin_info);
	}
}
