<?php

class shopSeofilterPluginFilterTreeSaveChangesController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');

		$state = json_decode($state_json, true);

		if (!is_array($state))
		{
			return;
		}

		$storage = new shopSeofilterFilterTreeSettingsStorage();

		$storage->updateCategoryState($state['category_is_enabled']);
		$storage->updateCategoryFeatureState($state['category_feature_is_enabled']);
		$storage->updateCategoryFeatureValueState($state['category_feature_value_is_enabled']);

		$storage->updateCategoriesStorefrontSelection($state['categories_storefront_selection']);
		$storage->updateCategoriesFeaturesStorefrontSelection($state['categories_features_storefront_selection']);
	}
}