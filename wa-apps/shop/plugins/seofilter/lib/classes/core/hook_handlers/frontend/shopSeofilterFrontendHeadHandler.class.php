<?php

class shopSeofilterFrontendHeadHandler extends shopSeofilterHookHandler
{
	private $hook_content = '';

	/** @var waSmarty3View|waView */
	private $view;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->view = wa()->getView();
	}

	protected function handle()
	{
		$this->hook_content = '';

		if ($this->settings->sitemap_lazy_generation && !waRequest::isXMLHttpRequest())
		{
			$sitemap_cache = new shopSeofilterSitemapCache();
			$sitemap_cache->step();
		}

		$context = $this->plugin_environment->getContext();
		if ($this->plugin_routing->getFilter() && $context)
		{
			$context->apply();
		}

		$catalogreviews_helper = new shopSeofilterCatalogreviewsPluginHelper();
		$category = $this->plugin_routing->getCategory();
		if (!$category && $catalogreviews_helper->isCurrentPageReviewsCatalogPage())
		{
			$category = $catalogreviews_helper->getCurrentPageCategory();
		}

		if ($category)
		{
			$this->assignHookTemplateVariables($category, $catalogreviews_helper->getPluginEnv());

			$this->hook_content = $this->view->fetch(shopSeofilterHelper::getPath('templates/handlers/FrontendHead.html'));
		}

		return $this->hook_content;
	}

	/**
	 * @param array $category
	 * @param shopCatalogreviewsPluginEnv|null $catalogreviews_env
	 */
	private function assignHookTemplateVariables($category, $catalogreviews_env)
	{
		$is_catalogreviews_page = $catalogreviews_env && $catalogreviews_env->isReviewsCatalogPage();

		$yandex_counter_code = false;
		$current_feature_value_ids = false;
		if (!$is_catalogreviews_page)
		{
			$yandex_counter_code = $this->settings->getYandexCounterCode(shopSeofilterStorefrontModel::getCurrentStorefront());

			if ($this->settings->block_empty_feature_values)
			{
				$current_feature_value_ids = $this->getCurrentFeatureValueIdsForBlocking();
			}
		}

		$current_filter = $this->getCurrentFilter($catalogreviews_env);

		$current_filter_params = $current_filter
			? $this->prepareParamsForJs($category['id'], $current_filter)
			: array();

		$this->view->assign('seofilter_frontend_head_state', array(
			'is_catalogreviews_page' => $is_catalogreviews_page,
			'category_url' => $this->getCategoryUrl($category, $catalogreviews_env),
			'yandex_counter_code' => $yandex_counter_code,

			'current_filter_params' => $current_filter_params,
			'filter_url' => $this->getFilterUrl($category['id'], $current_filter, $catalogreviews_env),

			'feature_value_ids' => $current_feature_value_ids,

			'keep_page_number_param' => $this->settings->keep_page_number_param,
			'block_empty_feature_values' => $this->settings->block_empty_feature_values,
			'excluded_get_params' => $this->settings->excluded_get_params,
			'stop_propagation_in_frontend_script' => $this->settings->stop_propagation_in_frontend_script,

			'plugin_url' => wa()->getAppStaticUrl('shop/plugins/seofilter'),
		));
	}

	/**
	 * @param $category
	 * @param shopCatalogreviewsPluginEnv|null $catalogreviews_env
	 * @return string
	 */
	private function getCategoryUrl($category, $catalogreviews_env)
	{
		if ($catalogreviews_env && $catalogreviews_env->isReviewsCatalogPage())
		{
			return $catalogreviews_env->getPluginRouting()->getReviewsPageUrl($category);
		}
		else
		{
			$category_route_params = array(
				'category_url' => waRequest::param('url_type') == shopSeofilterWaShopUrlType::PLAIN
					? $category['url']
					: $category['full_url']
			);

			return wa()->getRouteUrl('shop/frontend/category', $category_route_params);
		}
	}

	/**
	 * @param $category_id
	 * @param shopSeofilterFilter|null $current_filter
	 * @param shopCatalogreviewsPluginEnv|null $catalogreviews_env
	 * @return string
	 */
	private function getFilterUrl($category_id, $current_filter, $catalogreviews_env)
	{
		if (!$current_filter)
		{
			return '';
		}

		$filter_url = $current_filter->getFrontendCategoryUrl($category_id);

		return $catalogreviews_env && $catalogreviews_env->isReviewsCatalogPage()
			? $catalogreviews_env->getPluginRouting()->transformCategorySeofilterUrlToCategoryReviewsUrl($filter_url)
			: $filter_url;
	}

	/**
	 * @param shopCatalogreviewsPluginEnv|null $catalogreviews_env
	 * @return shopSeofilterFilter|null
	 */
	private function getCurrentFilter($catalogreviews_env)
	{
		$filter = $this->plugin_routing->getFilter();
		if (!$filter && $catalogreviews_env)
		{
			$filter = $catalogreviews_env->getSeofilterFilter();
		}

		return $filter;
	}

	private function prepareParamsForJs($category_id, shopSeofilterFilter $filter)
	{
		$result = array();

		$filters = shopSeofilterHelper::getViewFilters($category_id);

		$category_filters = array();
		if (is_array($filters))
		{
			foreach ($filters as $view_filter)
			{
				if (isset($view_filter['id']))
				{
					$category_filters[$view_filter['id']] = true;
				}
				elseif (isset($view_filter['code']))
				{
					$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureByCode($view_filter['code']);
					if ($feature)
					{
						$category_filters[$feature['id']] = true;
					}
				}
			}
		}

		foreach ($filter->featureValues as $feature_value)
		{
			$feature = $feature_value->feature;

			if ($feature && $feature_value->value_id && !isset($category_filters[$feature->id]))
			{
				$result[] = array(
					'name' => $feature->code . '[]',
					'value' => $feature_value->value_id,
				);
			}
		}
		unset($feature_value);

		return $result;
	}

	private function getCurrentFeatureValueIdsForBlocking()
	{
		return $this->plugin_environment->getCurrentFeatureValueIdsForBlocking($this->plugin_routing);
	}
}
