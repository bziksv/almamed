<?php

class shopSeofilterPluginFilterTreeGetCategoryFeatureValueFilterController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$this->response['filter_exists'] = false;

		$category_id = waRequest::request('category_id');
		$feature_id = waRequest::request('feature_id');
		$value_id = waRequest::request('value_id');

		if (!$category_id || !$feature_id || !$value_id)
		{
			return;
		}

		$storage = new shopSeofilterFilterTreeSettingsStorage();
		$filter = $storage->getFilterForFeatureValue($feature_id, $value_id);

		if (!$filter)
		{
			return;
		}

		$rule = $storage->getFilterCategoryPersonalRule($filter->id, $category_id);
		$has_personal_rule = true;

		if (!$rule)
		{
			$rule = new shopSeofilterFilterPersonalRule();
			$has_personal_rule = false;
		}

		$rule_attributes = array(
			'is_enabled' => !!$rule->is_enabled,
			'storefronts_use_mode' => $rule->storefronts_use_mode,
			'rule_storefronts' => $rule->rule_storefronts,
			'seo_h1' => $rule->seo_h1 ? $rule->seo_h1 : '',
			'seo_description' => $rule->seo_description ? $rule->seo_description : '',
			'meta_title' => $rule->meta_title ? $rule->meta_title : '',
			'meta_description' => $rule->meta_description ? $rule->meta_description : '',
			'meta_keywords' => $rule->meta_keywords ? $rule->meta_keywords : '',
			'additional_description' => $rule->additional_description ? $rule->additional_description : '',
		);

		$this->response['filter_exists'] = true;
		$this->response['feature_value_filter'] = array(
			'id' => $filter->id,
			'seo_name' => $filter->seo_name,
			'is_enabled' => !!$filter->is_enabled,
			'categories_personal_rule' => array(
				$category_id => $rule_attributes,
			),
			'has_personal_rule' => array(
				$category_id => $has_personal_rule,
			),
		);
	}
}