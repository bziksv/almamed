<?php

class shopBreadcrumbsPluginSettingsGetThemeSettingsController extends waJsonController
{
	public function execute()
	{
		$app_id = waRequest::get('app');
		$theme_id = waRequest::get('theme_id');

		$template_storage_factory = new shopBreadcrumbsAppTemplateStorageFactory();
		$template_storage = $template_storage_factory->getAppThemeTemplateStorage($app_id, $theme_id);

		$this->response = array(
			'templates' => $template_storage->getTemplateSettings(),
		);
	}
}