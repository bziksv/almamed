<?php

class shopSeofilterFiltersFrontendStorage extends shopSeofilterFiltersStorage
{
	private $setting;

	private $tree_settings;
	private $storage;
	private $filter_features_validator;

	public function __construct()
	{
		$this->setting = shopSeofilterBasicSettingsModel::getSettings();

		$this->tree_settings = new shopSeofilterFilterTreeSettings();
		$this->storage = new shopSeofilterFiltersStorage();
		$this->filter_features_validator = new shopSeofilterFilterFeaturesValidator();
	}

	// TODO фронтенд версия
	public function getById($filter_id)
	{
		$filter = parent::getById($filter_id);

		return $filter && $filter->is_enabled
			? $filter
			: null;
	}

	public function getByFilterParams($storefront, $category_id, $filter_params, $currency)
	{
		if (!$this->tree_settings->isPluginEnabledOnStorefrontCategory($storefront, $category_id))
		{
			return null;
		}

		if (
			$this->setting->consider_category_filters
			&& !$this->filter_features_validator->validateCategoryParams($category_id, $filter_params)
		)
		{
			return null;
		}

		$filter = $this->storage->getByFilterParams($storefront, $category_id, $filter_params, $currency);
		if (
			!$filter
			|| !$this->tree_settings->isFilterEnabled($storefront, $category_id, $filter)
			// || $filter->countProducts($category_id, $currency) == 0  TODO дать выбор способа подсчета (через productsCollection или sitemapDataSerializeCache)
		)
		{
			return null;
		}

		return $filter;
	}
}