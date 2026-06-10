<?php

class shopSeofilterFilterUrlHelper
{
	private $plugin_routing;
	private $plugin_environment;
	private $settings;
	private $shop_route;
	private $storefront;
	private $currency;

	private $category_model;
	private $filters_cached_storage;
	private $seofilter_url;

	private $catalogreviews_plugin_helper;

	private $current_filtration_params_normalized;



	private $cached_categories = array();
	private $cached_features_values_ids = array();
	private $cached_filter_ids = array();

	public function __construct(
		shopSeofilterRouting $plugin_routing,
		shopSeofilterPluginEnvironment $plugin_environment,
		shopSeofilterPluginSettings $settings,
		$shop_route,
		$storefront,
		$currency
	)
	{
		$this->plugin_routing = $plugin_routing;
		$this->plugin_environment = $plugin_environment;
		$this->settings = $settings;
		$this->shop_route = $shop_route;
		$this->storefront = $storefront;
		$this->currency = $currency;


		$route_url_type = is_array($shop_route) && array_key_exists('url_type', $shop_route)
			? $shop_route['url_type']
			: '0';

		$this->category_model = new shopCategoryModel();
		$this->filters_cached_storage = new shopSeofilterFiltersCachedFrontendStorage($this->settings);
		$this->seofilter_url = new shopSeofilterFilterUrl($settings->url_type, $route_url_type);

		$this->catalogreviews_plugin_helper = new shopSeofilterCatalogreviewsPluginHelper();


		$this->current_filtration_params_normalized = shopSeofilterFilterFeatureValuesHelper::normalizeParams(waRequest::get());
	}

	public function getFilterUrl($feature_id, $value_id, $feature_code = null, $specific_category = null)
	{
		$is_specific_category = $specific_category !== null;

		$is_enabled = $this->catalogreviews_plugin_helper->isEnabled();
		$is_current_page_reviews_catalog_page = $this->catalogreviews_plugin_helper->isCurrentPageReviewsCatalogPage();
		if (
			!$is_specific_category
			&& $is_enabled
			&& $is_current_page_reviews_catalog_page
		)
		{
			$filtration_category = $this->catalogreviews_plugin_helper->getCurrentPageCategory();

			$cached_filter = $this->searchFilterByParams($feature_id, $value_id, $feature_code, $filtration_category, $is_specific_category, false);

			return $this->getReviewsCatalogFilterUrl($filtration_category, $cached_filter);
		}
		else
		{
			$filtration_category = $this->getFiltrationCategory($specific_category);

			$cached_filter = $this->searchFilterByParams($feature_id, $value_id, $feature_code, $filtration_category, $is_specific_category, true);

			return $this->getCategoryFilterUrl($filtration_category, $cached_filter);
		}
	}

	/**
	 * @param array $filtration_category
	 * @param shopSeofilterFilterCached $cached_filter
	 * @return string|false   если false, значит ссылка для данной фильтрации не должна выводиться на текущей странице
	 */
	private function getCategoryFilterUrl($filtration_category, $cached_filter)
	{
		if (!$cached_filter)
		{
			return '';
		}

		$frontend_filter = $this->plugin_routing->getFrontendFilter();
		if (($frontend_filter && $frontend_filter->filter->id == $cached_filter->getId()) || $this->seofilter_url->haveShopUrlCollision($cached_filter->getUrl()))
		{
			return false;
		}

		return $this->seofilter_url->getFrontendPageUrl($filtration_category, $cached_filter->getUrl());
	}

	/**
	 * @param $filtration_category
	 * @param shopSeofilterFilterCached|null $cached_filter
	 * @return string
	 */
	private function getReviewsCatalogFilterUrl($filtration_category, $cached_filter)
	{
		if (!$cached_filter)
		{
			return '';
		}

		if (!$this->catalogreviews_plugin_helper->isFilterEnabledOnCategory($filtration_category['id'], $cached_filter->getId()))
		{
			return '';
		}

		$filter_url = $this->seofilter_url->getFrontendPageUrl($filtration_category, $cached_filter->getUrl());

		return $this->catalogreviews_plugin_helper->transformCategorySeofilterUrlToCategoryReviewsUrl($filter_url);
	}

	/**
	 * @param $feature_id
	 * @param $value_id
	 * @param $feature_code
	 * @param array $filtration_category
	 * @param bool $is_specific_category
	 * @param bool $check_products
	 * @return shopSeofilterFilterCached|null
	 */
	private function searchFilterByParams(
		$feature_id,
		$value_id,
		$feature_code,
		$filtration_category,
		$is_specific_category,
		$check_products
	)
	{
		list($feature_id, $feature_code) = $this->getFeatureIdAndCode($feature_id, $feature_code);

		if (!$filtration_category || $feature_code === '')
		{
			return null;
		}

		$current_filtration_params_by_code = $is_specific_category
			? array()
			: $this->current_filtration_params_normalized;


		$additional_params = array($feature_code => array((string)$value_id));

		if ($this->paramsAreEqual($current_filtration_params_by_code, $additional_params))
		{
			return null;
		}

		//$filtration_params_by_code = array_merge_recursive($current_filtration_params_by_code, $additional_params);
		$filtration_params_by_code = shopSeofilterFilterFeatureValuesHelper::arrayMergeRecursive($current_filtration_params_by_code, $additional_params);
		foreach ($this->settings->excluded_get_params as $excluded_param)
		{
			unset ($filtration_params_by_code[$excluded_param]);
		}

		if ($check_products && !$this->isHaveProducts($feature_id, $value_id, $filtration_category['id'], $is_specific_category))
		{
			return null;
		}

		$cached_filter = $this->filters_cached_storage->getByFilterParams(
			$this->storefront,
			$filtration_category['id'],
			$filtration_params_by_code,
			$this->currency
		);

		if ($cached_filter === null)
		{
			return null;
		}

		if ($check_products && $this->settings->use_sitemap_cache_for_checks)
		{
			$cached_filter_ids = $this->getCachedFilterIds($filtration_category['id']);
			if (!isset($cached_filter_ids[$cached_filter->getId()]))
			{
				return null;
			}
		}

		return $cached_filter;
	}

	private function getFiltrationCategory($specific_category)
	{
		if ($specific_category === null)
		{
			$current_category = $this->plugin_routing->getCategory();
		}
		else
		{
			if (is_array($specific_category))
			{
				$current_category = $specific_category;
			}
			elseif (wa_is_int($specific_category))
			{
				$current_category = $this->getCategoryById($specific_category);
			}
			else
			{
				$current_category = null;
			}
		}

		return $current_category;
	}

	private function getFeatureIdAndCode($feature_id, $feature_code)
	{
		if (is_string($feature_code) && $feature_code !== '')
		{
			if (!$feature_id)
			{
				$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureByCode($feature_id);

				$feature_id = $feature ? $feature->id : $feature_id;
			}
		}
		else
		{
			$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureById($feature_id);

			$feature_code = $feature ? $feature->code : $feature_code;
		}

		return array($feature_id, $feature_code);
	}

	private function getCurrentFeatureValueIds($filtration_category_id, $is_specific_category)
	{
		if ($is_specific_category)
		{
			if (!isset($this->cached_features_values_ids[$filtration_category_id]))
			{
				$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $filtration_category_id);
				$this->cached_features_values_ids[$filtration_category_id] = $collection->getFeatureValueIds();
			}

			return $this->cached_features_values_ids[$filtration_category_id];
		}
		else
		{
			return $this->plugin_environment->getCurrentFeatureValueIds();
		}
	}

	private function isHaveProducts($feature_id, $value_id, $filtration_category_id, $is_specific_category)
	{
		$current_filtration_params_by_feature_id = $is_specific_category
			? array()
			: $this->plugin_environment->getCurrentFilterParamsByFeatureId();

		$current_feature_value_ids = $this->getCurrentFeatureValueIds($filtration_category_id, $is_specific_category);

		return is_array($current_feature_value_ids)
			&& (
				in_array($value_id, ifset($current_feature_value_ids, $feature_id, array()))
				|| (count($current_feature_value_ids) && isset($current_filtration_params_by_feature_id[$feature_id]))
			);
	}


	private function getCategoryById($id)
	{
		if (!array_key_exists($id, $this->cached_categories))
		{
			$this->cached_categories[$id] = $this->category_model->getById($id);
		}

		return $this->cached_categories[$id];
	}

	/**
	 * @param $arr1
	 * @param $arr2
	 * @return bool
	 */
	private function paramsAreEqual($arr1, $arr2)
	{
		return count($arr1) === count($arr2) && count(array_udiff_assoc($arr1, $arr2, array($this, 'compareParamsArrays'))) == 0;
	}

	private function compareParamsArrays($a, $b)
	{
		if (is_array($a) && is_array($b))
		{
			return count(array_diff($a, $b)) == 0
				? 0
				: 1;
		}
		elseif (!is_array($a) && !is_array($b))
		{
			return $a == $b
				? 0
				: 1;
		}
		else
		{
			return 1;
		}
	}

	private function getCachedFilterIds($category_id)
	{
		if (!array_key_exists($category_id, $this->cached_filter_ids))
		{
			$cache_model = new shopSeofilterSitemapCacheModel();
			$filter_ids_raw = $cache_model->select('filter_ids')
				->where('storefront = :storefront', array('storefront' => $this->storefront))
				->where('category_id = :category_id', array('category_id' => $category_id))
				->fetchField();

			$filter_ids = array();
			if (trim($filter_ids_raw) !== '')
			{
				$filter_ids = array_fill_keys(explode(',', $filter_ids_raw), true);
			}

			$this->cached_filter_ids[$category_id] = $filter_ids;
		}

		return $this->cached_filter_ids[$category_id];
	}
}
