<?php

class shopSeofilterPluginFilterTreeDeleteFilterCategoryPersonalRuleController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$filter_id = waRequest::post('filter_id');
		$category_id = waRequest::post('category_id');

		if (!$filter_id || !$category_id)
		{
			return;
		}

		$storage = new shopSeofilterFilterTreeSettingsStorage();
		$storage->deleteFilterCategoryPersonalRule($filter_id, $category_id);
	}
}