<?php

class shopSeofilterPluginFilterTreeSaveFilterCategoryPersonalRuleController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$filter_id = waRequest::post('filter_id');
		$category_id = waRequest::post('category_id');
		$rule_params_json = waRequest::post('rule');

		$rule_params = json_decode($rule_params_json, true);

		if (!$filter_id || !$category_id || !is_array($rule_params))
		{
			return;
		}

		$storage = new shopSeofilterFilterTreeSettingsStorage();
		$storage->updateFilterCategoryPersonalRule($filter_id, $category_id, $rule_params);
	}
}