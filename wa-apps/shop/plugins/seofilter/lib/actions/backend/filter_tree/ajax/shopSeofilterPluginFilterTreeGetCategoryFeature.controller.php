<?php

class shopSeofilterPluginFilterTreeGetCategoryFeatureController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$category_id = waRequest::request('category_id');
		$feature_id = waRequest::request('feature_id');

		$this->response = array(
			'category_feature_settings' => $this->getCategoryFeatureSettings($category_id, $feature_id),
		);
	}

	private function getCategoryFeatureSettings($category_id, $feature_id)
	{
		if (!$category_id || !$feature_id)
		{
			return null;
		}

		$storage = new shopSeofilterFilterTreeSettingsStorage();

		return $storage->getCategoryFeatureSettings($category_id, $feature_id);
	}
}