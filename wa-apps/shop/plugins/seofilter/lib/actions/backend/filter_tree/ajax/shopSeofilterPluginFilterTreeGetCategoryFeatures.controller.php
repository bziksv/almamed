<?php

class shopSeofilterPluginFilterTreeGetCategoryFeaturesController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$category_id = waRequest::request('category_id');

		$this->response = array(
			'category_features_settings' => $this->getCategoryFeatures($category_id),
		);
	}

	private function getCategoryFeatures($category_id)
	{
		if (!$category_id) {
			return null;
		}

		$storage = new shopSeofilterFilterTreeSettingsStorage();

		return $storage->getCategoryFeaturesSettings($category_id);
	}
}