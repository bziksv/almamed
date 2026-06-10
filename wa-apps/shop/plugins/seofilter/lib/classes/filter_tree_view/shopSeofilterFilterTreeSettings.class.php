<?php

class shopSeofilterFilterTreeSettings
{
	/** @var shopSeofilterFilterTreeSettingsStorage */
	private static $_storage = null;

	private static $storefront_category_cache = array();

	private static $storefront_category_feature_cache = array();

	/** @var shopSeofilterFilterTreeSettingsStorage */
	private $storage;

	public function __construct()
	{
		if (self::$_storage === null)
		{
			self::$_storage = new shopSeofilterFilterTreeSettingsStorage();
		}

		$this->storage = self::$_storage;
	}

	public function isPluginEnabledOnStorefrontCategory($storefront, $category_id)
	{
		if (!array_key_exists($storefront, self::$storefront_category_cache))
		{
			self::$storefront_category_cache[$storefront] = array();
		}

		if (!array_key_exists($category_id, self::$storefront_category_cache[$storefront]))
		{
			self::$storefront_category_cache[$storefront][$category_id] = $this->storage->isPluginEnabledOnStorefrontCategory($storefront, $category_id);
		}

		return self::$storefront_category_cache[$storefront][$category_id];
	}

	public function isFilterEnabled($storefront, $category_id, shopSeofilterFilter $filter)
	{
		if (!$filter->is_enabled)
		{
			return false;
		}

		if (!$this->isPluginEnabledOnStorefrontCategory($storefront, $category_id))
		{
			return false;
		}

		if ($filter->feature_values_count != 1 || $filter->feature_value_ranges_count != 0)
		{
			return true;
		}


		$feature_id = null;
		foreach ($filter->featureValues as $feature_value)
		{
			$feature_id = $feature_value->feature_id;
		}

		if (!$feature_id)
		{
			return false;
		}


		if (!array_key_exists($storefront, self::$storefront_category_feature_cache))
		{
			self::$storefront_category_feature_cache[$storefront] = array();
		}

		if (!array_key_exists($category_id, self::$storefront_category_feature_cache[$storefront]))
		{
			self::$storefront_category_feature_cache[$storefront][$category_id] = array();
		}

		if (!array_key_exists($feature_id, self::$storefront_category_feature_cache[$storefront][$category_id]))
		{
			self::$storefront_category_feature_cache[$storefront][$category_id][$feature_id] = $this->storage->isPluginEnabledOnStorefrontCategoryFeature($storefront, $category_id, $feature_id);
		}


		return self::$storefront_category_feature_cache[$storefront][$category_id][$feature_id];
	}
}