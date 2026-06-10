<?php

class shopSeofilterPluginProductfiltersSettingsGetStorefrontSettingsController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$storefront = waRequest::get('storefront');

		$category_settings_model = new shopSeofilterProductfiltersCategorySettingsModel();
		$settings = new shopSeofilterProductfiltersSettings($storefront);

		$this->response = array(
			'settings' => $settings->getSettings(),
			'category_feature_rules' => array($storefront => array()),
			'categories_settings' => $category_settings_model->getCategoriesSettings($storefront),
		);
	}
}