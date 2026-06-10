<?php

class shopSeofilterPluginProductfiltersSettingsGetCategoryRuleController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$storefront = waRequest::get('storefront');
		$category_id = waRequest::get('category_id');

		$collection = new shopProductsCollection('category/' . $category_id);
		$feature_value_ids = $collection->getFeatureValueIds();

		$feature_ids = array_keys($feature_value_ids);

		$rule_settings = new shopSeofilterProductfiltersCategoryFeatureRules($storefront);

		$rules_by_category = $rule_settings->getRules();

		$rule = isset($rules_by_category[$category_id])
			? $rules_by_category[$category_id]
			: array();

		foreach ($feature_ids as $feature_id)
		{
			if (!isset($rule[$feature_id]))
			{
				$rule[$feature_id] = array(
					'link_category_id' => 0,
					'display_link' => true,
				);
			}
		}

		$this->response = $rule;
	}
}