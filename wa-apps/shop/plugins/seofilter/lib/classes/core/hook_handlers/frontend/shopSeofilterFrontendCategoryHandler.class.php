<?php

class shopSeofilterFrontendCategoryHandler extends shopSeofilterHookHandler
{
	private $currency;

	public function __construct($params = null)
	{
		parent::__construct($params);

		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();
		$this->currency = $config instanceof shopConfig
			? $config->getCurrency(false)
			: waRequest::param('currency', 'RUB');
	}

	protected function handle()
	{
		if (!$this->plugin_routing->getCategory())
		{
			return '';
		}

		$this->setCurrentFeatureValueIds();

		if ($this->settings->use_custom_products_collection)
		{
			$this->reAssignProducts($this->plugin_routing->getCategoryId());
		}

		if ($this->plugin_routing->isSeofilterPage())
		{
			$this->initializeContext();
			$this->setResponseHttpCode();

			$this->plugin_routing->restoreInitialUrl();
		}

		if (waRequest::isXMLHttpRequest())
		{
			wa()->getResponse()->addHeader('Cache-Control', 'no-store');

			return $this->fetchDataForAjaxHandlers();
		}
		else
		{
			return '';
		}
	}


	private function setCurrentFeatureValueIds()
	{
		$params = $this->plugin_routing->isSeofilterPage()
			? $this->plugin_routing->getFilter()->getFeatureValuesAsFilterParams(shopSeofilterFilter::PARAMS_WITHOUT_RANGE_VALUES)
			: waRequest::get();

		$params = shopSeofilterFilterFeatureValuesHelper::normalizeParams($params);

		$filter_params_by_feature_id = array();
		foreach ($params as $code => $value_ids)
		{
			$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureByCode($code);
			if ($feature)
			{
				$filter_params_by_feature_id[$feature->id] = $value_ids;
			}
		}

		$current_feature_value_ids = shopSeofilterFilterFeatureValuesHelper::fastGetFeatureValueIds(
			$this->plugin_routing->getCategoryId(),
			$filter_params_by_feature_id,
			waRequest::param('drop_out_of_stock') == 2
		);

		$this->plugin_environment->setFilterParamsByFeatureId($filter_params_by_feature_id);
		$this->plugin_environment->setFeatureValueIds($current_feature_value_ids);
	}


	private function reAssignProducts($category_id)
	{
		$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);

		$collection->filters(waRequest::get());
		$limit = (int)waRequest::cookie('products_per_page');
		if (!$limit || $limit < 0 || $limit > 500)
		{
			$limit = wa('shop')->getConfig()->getOption('products_per_page');
		}
		$page = waRequest::get('page', 1, 'int');
		if ($page < 1)
		{
			$page = 1;
		}
		$offset = ($page - 1) * $limit;

		$products = $collection->getProducts('*', $offset, $limit);
		$count = $collection->count();

		$pages_count = ceil((float)$count / $limit);
		wa()->getView()->assign('pages_count', $pages_count);

		wa()->getView()->assign('products', $products);
		wa()->getView()->assign('products_count', $count);
	}

	private function initializeContext()
	{
		$context = new shopSeofilterCategoryContext(
			$this->plugin_routing->getFrontendFilter(),
			$this->currency,
			$this->plugin_routing->getStorefront(),
			$this->plugin_routing->getCategoryId(),
			waRequest::get('page', 1, waRequest::TYPE_INT)
		);

		$context->apply();

		$this->plugin_environment->setContext($context);
	}

	private function setResponseHttpCode()
	{
		if (!$this->plugin_routing->getFilter()->countProducts($this->plugin_routing->getCategoryId(), $this->currency))
		{
			$empty_page_http_code = $this->plugin_routing->getFilter()->empty_page_http_code;
			if (!is_string($empty_page_http_code) || strlen(trim($empty_page_http_code)) !== 3)
			{
				$empty_page_http_code = $this->settings->empty_page_http_code;
			}

			wa()->getResponse()->setStatus($empty_page_http_code);
		}
	}

	private function fetchDataForAjaxHandlers()
	{
		$view = wa()->getView();

		if ($this->plugin_routing->isSeofilterPage())
		{
			$current_title = wa()->getResponse()->getTitle();
			$meta_title = trim($current_title) == ''
				? shopCategoryModel::getDefaultMetaTitle($this->plugin_routing->getCategory())
				: $current_title;

			$view->assign(array(
				'seofilter_category_title' => $meta_title,
			));
		}
		else
		{
			$seo_helper = new shopSeofilterSeoHelper();

			$seo_data = $seo_helper->getFrontendCategorySeoData(
				$this->plugin_routing->getStorefront(),
				$this->plugin_routing->getCategory(),
				waRequest::get('page', 1, waRequest::TYPE_INT),
				wa()->getView()
			);

			$view->assign(array(
				'seofilter_category_h1' => $seo_data->h1,
				'seofilter_category_description' => $seo_data->description,
				'seofilter_category_additional_description' => $seo_data->additional_description,
				'seofilter_category_title' => $seo_data->meta_title,
			));
		}

		$feature_value_ids = false;
		if ($this->settings->block_empty_feature_values)
		{
			$feature_value_ids = $this->getCurrentFeatureValueIdsForBlocking();
		}

		$view->assign(array(
			'seofilter_current_feature_value_ids' => $feature_value_ids,
		));

		return $view->fetch(shopSeofilterHelper::getPath('templates/handlers/FrontendCategory.html'));
	}

	private function getCurrentFeatureValueIdsForBlocking()
	{
		return $this->plugin_environment->getCurrentFeatureValueIdsForBlocking($this->plugin_routing);
	}
}
