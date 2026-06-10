<?php

class shopSeofilterViewHelper extends waViewHelper
{
	private static $filter_url_helper = null;
	private static $sitemap_cached_filter_ids = array();

	private static $currency = null;

	private static $_info = null;
	private static $catalogreviews_plugin_helper;

	public static function paginationDecorate($pagination)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if (!$settings->is_enabled)
		{
			return $pagination;
		}

		$excluded_get_params = $settings->excluded_get_params;
		$excluded_get_params[] = '_';

		$plugin_routing = shopSeofilterRouting::instance();

		$current_filter = self::getCurrentFilter();
		if (!$current_filter)
		{
			return $pagination;
		}

		preg_match_all('/href="(.*?)"/', $pagination, $matches);


		$pagination_links = array();
		foreach ($matches[1] as $match)
		{
			$pagination_links[$match] = $match;
		}
		unset($matches);

		if (count($pagination_links) == 0)
		{
			return $pagination;
		}

		$special_params = shopSeofilterFilterFeatureValuesHelper::getCurrentSpecialGetParams();

		foreach ($pagination_links as $key => $link)
		{
			$query = urldecode(parse_url($link, PHP_URL_QUERY));
			$params = null;
			parse_str($query, $params);

			foreach (array_keys($params) as $param)
			{
				if (!in_array($param, $special_params))
				{
					unset($params[$param]);
				}
			}

			foreach ($excluded_get_params as $excluded_param)
			{
				unset($params[$excluded_param]);
			}

			$link_query = count($params) == 0
				? ''
				: '?' . http_build_query($params);
			$link = $current_filter->getFrontendCategoryUrl($plugin_routing->getCategory());

			$catalogreviews_plugin_helper = self::getCatalogreviewsHelper();
			$catalogreviews_plugin_env = $catalogreviews_plugin_helper->getPluginEnv();
			if ($catalogreviews_plugin_helper->isCurrentPageReviewsCatalogPage() && $catalogreviews_plugin_env)
			{
				$link = $catalogreviews_plugin_env->getPluginRouting()->transformCategorySeofilterUrlToCategoryReviewsUrl($link);
			}

			$pagination = str_replace($key, $link . $link_query, $pagination);
		}

		return $pagination;
	}

	public static function sortUrl($sort, $name, $active_sort = null, $additional_sorts = array())
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$context = shopSeofilterPluginEnvironment::instance()->getContext();

		$possible_sorts = array(
			'rating' => 1,
			'create_datetime' => 1,
			'total_sales' => 1,
			'count' => 1,
			'stock' => 1,
		);
		if (is_array($additional_sorts))
		{
			foreach ($additional_sorts as $additional_sort)
			{
				$possible_sorts[$additional_sort] = 1;
			}
		}

		if (!$settings->is_enabled || !$context)
		{
			$shop_helper = self::getShopHelper();

			return $shop_helper->sortUrl($sort, $name, $active_sort);
		}

		if ($active_sort === null)
		{
			$active_sort = waRequest::get('sort');
		}

		$is_inverted = array_key_exists($sort, $possible_sorts);
		$data = array();
		$data['sort'] = $sort;
		if ($sort == $active_sort)
		{
			$data['order'] = waRequest::get('order', 'asc', 'string') == 'asc' ? 'desc' : 'asc';
		}
		else
		{
			$data['order'] = $is_inverted ? 'desc' : 'asc';
		}

		return '<a href="?'.http_build_query($data).'">'.$name.($sort == $active_sort ? ' <i class="sort-'.($data['order'] == 'asc' ? 'desc' : 'asc').'"></i>' : '').'</a>';
	}

	/**
	 * @param int $feature_id
	 * @param int $value_id
	 * @param string $feature_code
	 * @param array $specific_category   если задана, то игнорируем текущую категорию и фильтрацию
	 * @return string|false   если false, значит ссылка для данной фильтрации не должна выводиться на текущей странице
	 */
	public static function getFilterUrl($feature_id, $value_id, $feature_code = null, $specific_category = null)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if (!$settings->is_enabled)
		{
			return null;
		}

		return self::getFilterUrlHelper()->getFilterUrl($feature_id, $value_id, $feature_code, $specific_category);
	}

	/**
	 * @param int $filter_id
	 * @param int|array $category
	 * @param int $storefront_url_type
	 * @param bool $absolute
	 * @return null|string
	 */
	public static function getFrontendCategoryUrl($filter_id, $category, $storefront_url_type = null, $absolute = true)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if (!$settings->is_enabled)
		{
			return null;
		}

		if ($storefront_url_type === null)
		{
			$storefront_url_type = waRequest::param('url_type');
		}

		if (wa_is_int($category))
		{
			$category_id = $category;
			$model = new shopCategoryModel();
			$category = $model->getById($category_id);
		}

		$filter_ar = new shopSeofilterFilter();
		$filter = $filter_ar->getById($filter_id);

		return $filter && $category
			? $filter->getFrontendCategoryUrl($category, $storefront_url_type, $absolute)
			: null;
	}

	public static function getFilterByFeatureValues($filter_params, $category = null, $storefront = null)
	{
		if ($storefront === null)
		{
			$storefront = shopSeofilterStorefrontModel::getCurrentStorefront();
		}

		if ($category === null)
		{
			$category = wa()->getView()->getVars('category');
		}

		if (!is_array($category))
		{
			return null;
		}

		$storage = new shopSeofilterFiltersFrontendStorage();
		$currency = self::getCurrency();

		$filter = $storage->getByFilterParams($storefront, $category['id'], $filter_params, $currency);

		if (!$filter || !self::filterHasProducts($filter, $storefront, $category['id'], $currency))
		{
			return null;
		}

		$filter_attributes = $filter->getAttributes();
		$filter_attributes['frontend_url'] = $filter->getFrontendCategoryUrl($category);

		return $filter_attributes;
	}

	public static function getValueUrl($feature_code, $value, $product = null)
	{
		return shopSeofilterProductfiltersViewHelper::getValueUrl($feature_code, $value, $product);
	}

	public static function getValueLinkHtml($feature_code, $value, $product = null)
	{
		return shopSeofilterProductfiltersViewHelper::getValueLinkHtml($feature_code, $value, $product);
	}

	public static function wrapFeatureValues($product_feature_values, $product = null)
	{
		return shopSeofilterProductfiltersViewHelper::wrapFeatureValues($product_feature_values, $product);
	}

	/**
	 * @return shopViewHelper
	 */
	private static function getShopHelper()
	{
		if (!isset(self::$helpers['shop']))
		{
			$helper = new waViewHelper(wa()->getView());
			$helper->shop;
		}

		return self::$helpers['shop'];
	}

	/**
	 * @return shopSeofilterFilterUrlHelper
	 */
	private static function getFilterUrlHelper()
	{
		if (self::$filter_url_helper === null)
		{
			self::$filter_url_helper = new shopSeofilterFilterUrlHelper(
				shopSeofilterRouting::instance(),
				shopSeofilterPluginEnvironment::instance(),
				shopSeofilterBasicSettingsModel::getSettings(),
				wa()->getRouting()->getRoute(),
				shopSeofilterStorefrontModel::getCurrentStorefront(),
				self::getCurrency()
			);
		}

		return self::$filter_url_helper;
	}


	/**
	 * @return mixed|null
	 */
	private static function getCurrency()
	{
		if (self::$currency === null)
		{
			/** @var shopConfig $shop_config */
			$shop_config = wa('shop')->getConfig();
			self::$currency = $shop_config->getCurrency(false);
		}

		return self::$currency;
	}

	public static function filterHasProducts(shopSeofilterFilter $filter, $storefront, $category_id, $currency)
	{
		$key = $storefront . '|' . $category_id;
		$filter_id = $filter->id;

		if (!array_key_exists($key, self::$sitemap_cached_filter_ids))
		{
			$settings = shopSeofilterBasicSettingsModel::getSettings();

			$cache_select_params = array(
				'storefront' => $storefront,
				'category_id' => $category_id,
			);
			if ($settings->cache_for_single_storefront)
			{
				unset($cache_select_params['storefront']);
			}

			$model = new shopSeofilterSitemapCacheModel();
			$cache_row = $model->getByField($cache_select_params);

			self::$sitemap_cached_filter_ids[$key] = array();

			if ($cache_row && $cache_row['filter_ids'] != '')
			{
				foreach (explode(',', $cache_row['filter_ids']) as $filter_id)
				{
					self::$sitemap_cached_filter_ids[$key][$filter_id] = $filter_id;
				}
			}
		}

		return count(self::$sitemap_cached_filter_ids[$key])
			? array_key_exists($filter_id, self::$sitemap_cached_filter_ids[$key])
			: $filter->countProducts($category_id, $currency) > 0;
	}

	public static function getAssetVersion()
	{
		if (self::$_info === null)
		{
			self::$_info = wa('shop')->getConfig()->getPluginInfo('seofilter');
		}

		return urlencode(self::$_info['version']);
	}

	/**
	 * @return shopSeofilterFilter|null
	 */
	private static function getCurrentFilter()
	{
		$catalogreviews_plugin_helper = self::getCatalogreviewsHelper();

		if ($catalogreviews_plugin_helper->isCurrentPageReviewsCatalogPage())
		{
			$plugin_env = $catalogreviews_plugin_helper->getPluginEnv();

			return $plugin_env
				? $plugin_env->getSeofilterFilter()
				: null;
		}
		else
		{
			$plugin_routing = shopSeofilterRouting::instance();

			$frontend_filter = $plugin_routing->getFrontendFilter();
			$context = shopSeofilterPluginEnvironment::instance()->getContext();

			return $frontend_filter && $context
				? $frontend_filter->filter
				: null;
		}
	}

	/**
	 * @return shopSeofilterCatalogreviewsPluginHelper
	 */
	private static function getCatalogreviewsHelper()
	{
		if (!isset(self::$catalogreviews_plugin_helper))
		{
			self::$catalogreviews_plugin_helper = new shopSeofilterCatalogreviewsPluginHelper();
		}

		return self::$catalogreviews_plugin_helper;
	}
}
