<?php

class shopSeofilterFilterUrl
{
	const SIMPLE_URL_PREFIX = 'filter';

	private static $_categories = array();

	private $plugin_url_type;
	private $storefront_url_type;
	private $category_root_url;
	private $category_model;
	private $current_route;

	private $param;

	public function __construct($plugin_url_type, $storefront_url_type, $param = null, $domain = null, $route = null)
	{
		$this->plugin_url_type = $plugin_url_type;
		$this->storefront_url_type = $storefront_url_type;

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$root_url = $settings->root_url;
		$this->category_root_url = $root_url ? $root_url : 'category';
		$this->category_model = new shopCategoryModel();

		$current_domain = $domain ? $domain : wa()->getRouting()->getDomain();
		$current_route = is_array($route) ? $route : wa()->getRouting()->getRoute();

		if (ifset($current_route['app']) !== 'shop')
		{
			$domain_routes = wa()->getRouting()->getByApp('shop');
			if (count(ifset($domain_routes[$current_domain], array())))
			{
				$current_route = reset($domain_routes[$current_domain]);
			}
			else
			{
				foreach ($domain_routes as $domain_route)
				{
					if (count($domain_route))
					{
						$current_route = reset($domain_route);
						break;
					}
				}
			}

			//if (ifset($current_route['app']) !== 'shop')
			//{
			//	throw new waException('At least one storefront of shop is required', 500);
			//}
		}

		$this->current_route = $current_route;
		$this->current_route['domain'] = $current_domain;

		$this->param = is_array($param)
			? $param
			: waRequest::param();
	}

	/**
	 * @param shopSeofilterFilter $filter
	 * @return string
	 */
	public static function generateUniqueUrl($filter)
	{
		static $transliteration = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
			'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
			'т' => 't', 'у' => 'u', 'ф' => 'f', 'ы' => 'y', 'э' => 'e', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
			'Д' => 'D', 'Е' => 'E', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
			'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Ы' => 'Y',
			'Э' => 'E', 'ё' => 'yo', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '',
			'ь' => '', 'ю' => 'yu', 'я' => 'ya', 'Ё' => 'YO', 'Х' => 'H', 'Ц' => 'TS', 'Ч' => 'CH', 'Ш' => 'SH',
			'Щ' => 'SHCH', 'Ъ' => '', 'Ь' => '', 'Ю' => 'YU', 'Я' => 'YA', ' ' => '-',
		);

		$base_string = $filter instanceof shopSeofilterFilter
			? $filter->seo_name
			: $filter;

		$url = strtr($base_string, $transliteration);
		$random_url = $url = substr(strtolower(preg_replace('/[^a-z_0-9\-]/i', '', $url)), 0, 200);

		$model = new waModel();

		$filter_table = $filter->tableName();
		$filter_table_sql = !$filter->getIsNewRecord() && $filter->id > 0
			? "SELECT 1 FROM {$filter_table} WHERE url = :url AND id != :id"
			: "SELECT 1 FROM {$filter_table} WHERE url = :url";

		$sql = "SELECT EXISTS ({$filter_table_sql}) OR EXISTS (SELECT 1 FROM shop_product WHERE url = :url) OR EXISTS (SELECT 1 FROM shop_category WHERE url = :url)";

		$params = array(
			'id' => $filter->id,
			'url' => $random_url,
		);

		if ($model->query($sql, $params)->fetchField() != '1')
		{
			return $random_url;
		}

		$feature_names = array();
		$feature_codes = array();
		foreach ($filter->all_feature_values as $feature_value)
		{
			if (!$feature_value->feature)
			{
				continue;
			}

			$feature_name = strtr($feature_value->feature->name, $transliteration);
			$feature_code = $feature_value->feature->code;
			if (isset($feature_codes[$feature_code]))
			{
				continue;
			}

			$feature_names[$feature_name] = strtolower(preg_replace('/[^a-z_0-9\-]/i', '', $feature_name));
			$feature_codes[$feature_code] = strtolower(preg_replace('/[^a-z_0-9\-]/i', '', $feature_code));
		}
		unset($feature_value);

		// пытаемся сгенерировать что-то не слишком стремное
		$random_url = substr(implode('-', $feature_codes) . '-' . $url, 0, 255);
		$params['url'] = $random_url;

		if ($model->query($sql, $params)->fetchField() != '1')
		{
			return $random_url;
		}

		$random_url = substr(implode('-', $feature_names) . '-' . $url, 0, 255);
		$params['url'] = $random_url;

		// уже не пытаемся
		while ($model->query($sql, $params)->fetchField() == '1')
		{
			$random_url = $url . '_' . rand(1, 9999999);
			$params['url'] = $random_url;
		}

		return $random_url;
	}

	public static function getUrlTypes()
	{
		return array(
			shopSeofilterPluginUrlType::SHORT => '/category/FilterName/',
			shopSeofilterPluginUrlType::SIMPLE => '/category/filter/FilterName/',
			shopSeofilterPluginUrlType::CATEGORY_JOIN => '/category-FilterName/',
		);
	}

	/**
	 * @param string|null $filter_url
	 * @return bool
	 */
	public function haveShopUrlCollision($filter_url = null)
	{
		if ($this->storefront_url_type == shopSeofilterWaShopUrlType::NATURAL)
		{
			return $this->isCategory($filter_url) || $this->startsLikeShopUrl() || $this->isProduct($filter_url);
		}
		else
		{
			return $this->isCategory($filter_url);
		}
	}

	/**
	 * @param array $category
	 * @param shopSeofilterFilter|string $filter_or_url
	 * @param bool $absolute
	 * @param string $domain
	 * @param string $route_url
	 * @return string
	 */
	public function getFrontendPageUrl($category, $filter_or_url, $absolute = false, $domain = null, $route_url = null)
	{
		$category_route_url = $this->fastCategoryUrl($category, $absolute, $domain, $route_url);

		if ($this->plugin_url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			return rtrim($category_route_url, '/') . '-' . $this->getFilterUrlSuffix($filter_or_url) . '/';
		}
		else
		{
			return $category_route_url . $this->getFilterUrlSuffix($filter_or_url) . '/';
		}
	}

	/**
	 * @param shopSeofilterFilter|string $filter_or_url
	 * @return string
	 */
	public function getFilterUrlSuffix($filter_or_url)
	{
		$url = $filter_or_url instanceof shopSeofilterFilter
			? $filter_or_url->url
			: $filter_or_url;

		$filter_url = $this->plugin_url_type == shopSeofilterPluginUrlType::SIMPLE
			? self::SIMPLE_URL_PREFIX . '/' . $url
			: $url;

		return $filter_url;
	}

	protected function getCategory()
	{
		$category_id = $this->param('category_id');
		$action = $this->param('action');
		if ($category_id)
		{
			return $this->category_model->getById($category_id);
		}

		$category_url = $this->param('seofilter_category_url');

		$category = $this->storefront_url_type == shopSeofilterWaShopUrlType::PLAIN
			? $this->category_model->getByField('url', $category_url)
			: $this->category_model->getByField('full_url', $category_url);

		if (!$category && $action != 'product')
		{
			$category_url = $this->param('category_url');
			$category = $this->storefront_url_type == shopSeofilterWaShopUrlType::PLAIN
				? $this->category_model->getByField('url', $category_url)
				: $this->category_model->getByField('full_url', $category_url);
		}

		return $category;
	}

	protected function startsLikeShopUrl()
	{
		$shop_routing_words = array(
			'login' => 1,
			'forgotpassword' => 1,
			'signup' => 1,
			'search' => 1,
			'data' => 1,
			'cart' => 1,
			'checkout' => 1,
			'compare' => 1,
			'tag' => 1,
			'buybuttons' => 1,
			'my' => 1,
		);
		$url_parts = explode('/', wa()->getRouting()->getCurrentUrl());

		return isset($shop_routing_words[ifset($url_parts[0], '')]);
	}

	/**
	 * @param string|null $filter_url
	 * @return bool
	 */
	protected function isProduct($filter_url = null)
	{
		// todo для category+filter

		if (!array_key_exists($this->storefront_url_type, self::$_categories))
		{
			self::$_categories[$this->storefront_url_type] = $this->getCategory();
		}

		if (!self::$_categories[$this->storefront_url_type])
		{
			return false;
		}

		$sql = '
SELECT COUNT(p.id)
FROM shop_product p
	JOIN shop_category_products pc
		ON pc.product_id = p.id AND p.url = :url
';

		$model = new waModel();

		if ($filter_url === null)
		{
			$filter_url = $this->param('filter_url');
		}

		$count = $model->query($sql, array('url' => $filter_url))->fetchField();

		return $count > 0;
	}

	/**
	 * @param string|null $filter_url
	 * @return bool
	 */
	protected function isCategory($filter_url = null)
	{
		$category = null;

		if ($this->storefront_url_type != shopSeofilterWaShopUrlType::PLAIN)
		{
			if ($filter_url === null)
			{
				$filter_url = $this->param('filter_url');
			}

			$full_category_url = '';

			if ($this->plugin_url_type == shopSeofilterPluginUrlType::SHORT)
			{
				$full_category_url = $this->param('seofilter_category_url') . '/' . $filter_url;
			}
			elseif ($this->plugin_url_type == shopSeofilterPluginUrlType::SIMPLE)
			{
				$full_category_url = $this->param('seofilter_category_url') . '/' . self::SIMPLE_URL_PREFIX . '/' . $filter_url;
			}
			elseif ($this->plugin_url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
			{
				$full_category_url = $this->param('seofilter_category_url') . '-' . $filter_url;
			}

			$m_category = new shopCategoryModel();
			$category = $m_category->getByField('full_url', $full_category_url);
		}

		return !!$category;
	}

	protected function param($name, $default = null)
	{
		return ifset($this->param[$name], $default);
	}

	private function fastCategoryUrl($category, $absolute = false, $domain = null, $route_url = null)
	{
		$category_url = $this->storefront_url_type == shopSeofilterWaShopUrlType::PLAIN
			? $category['url']
			: $category['full_url'];

		if ($domain === null)
		{
			$domain = $this->current_route['domain'];
		}

		if ($route_url === null)
		{
			$route_url = $this->current_route['url'];
		}

		if ($absolute)
		{
			return 'http' . (waRequest::isHttps() ? 's' : '') . '://' . $domain
			. '/' . rtrim($route_url, '*')
			. ($this->storefront_url_type == shopSeofilterWaShopUrlType::NATURAL ? '' : $this->category_root_url . '/')
			. $category_url . '/';
		}
		else
		{
			$domain_sub_route = '/';
			if ($domain && strpos($domain, '/') !== false)
			{
				if (preg_match('/\/(.+)/', $domain, $matches))
				{
					$domain_sub_route = '/' . trim($matches[1], '/') . '/';
				}
			}

			return  $domain_sub_route . rtrim($route_url, '*')
			. ($this->storefront_url_type == shopSeofilterWaShopUrlType::NATURAL ? '' : $this->category_root_url . '/')
			. $category_url . '/';
		}
	}
}
