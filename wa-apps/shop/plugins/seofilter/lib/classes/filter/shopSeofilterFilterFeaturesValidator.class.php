<?php

class shopSeofilterFilterFeaturesValidator
{
	private $category_storage;

	public function __construct()
	{
		$this->category_storage = new shopSeofilterWaCategoryStorage();
	}

	/**
	 * @param int|array $category_id
	 * @param array $get_params
	 * @return bool
	 */
	public function validateCategoryParams($category_id, $get_params)
	{
		if (!is_array($get_params) || !count($get_params))
		{
			return false;
		}

		if (is_array($category_id))
		{
			$category_id = $category_id['id'];
		}

		$category_filter_feature_ids = array();
		foreach ($this->category_storage->getFilterFeatureIds($category_id) as $feature_id)
		{
			$category_filter_feature_ids[$feature_id] = $feature_id;
		}

		if (!count($category_filter_feature_ids))
		{
			return false;
		}

		$features = shopSeofilterFilterFeatureValuesHelper::getFeatures('id', $category_filter_feature_ids, 'code');

		foreach (array_keys($get_params) as $feature_code)
		{
			$feature_code_lower = strtolower($feature_code);
			if ($feature_code_lower == 'price' || $feature_code_lower == 'price_min' || $feature_code_lower == 'price_max')
			{
				if (!array_key_exists('price', $category_filter_feature_ids))
				{
					return false;
				}
			}
			else
			{
				if (!array_key_exists($feature_code, $features) || !array_key_exists($features[$feature_code]->id, $category_filter_feature_ids))
				{
					return false;
				}
			}
		}

		return true;
	}
}
