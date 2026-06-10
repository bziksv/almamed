<?php

class shopBreadcrumbsPluginSettingsGetStorefrontSettingsController extends waJsonController
{
	public function execute()
	{
		$storefront = waRequest::get('storefront');

		$settings = new shopBreadcrumbsSettings($storefront);

		$this->response = array(
			'settings' => $settings->getRawSettings(),
		);
	}
}