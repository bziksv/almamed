<?php

class shopBreadcrumbsPluginSettingsSaveController extends waJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');

		$state = json_decode($state_json, true);

		$this->saveStorefrontSettings(ifset($state['storefront_settings']));
		$this->saveBlogSettings(ifset($state['blog_settings']));

		$this->saveThemeTemplates('shop', ifset($state['theme_templates']));
		$this->saveThemeTemplates('blog', ifset($state['blog_theme_templates']));

		$cache_path = shopBreadcrumbsSeoExtenderCache::getCachePath();

		waFiles::delete($cache_path, true);
	}


	public function saveStorefrontSettings($storefront_settings)
	{
		$settings_model = new shopBreadcrumbsSettingsModel();
		$seofilter_features_model = new shopBreadcrumbsSeofilterFeatureModel();

		$settings_fields = $settings_model->availableSettings();

		foreach ($storefront_settings as $storefront => $settings)
		{
			foreach ($settings as $setting => $value)
			{
				if (array_key_exists($setting, $settings_fields))
				{
					$settings_model->set($storefront, $setting, $value);
				}
				elseif ($setting === 'seofilter_feature_ids')
				{
					$seofilter_features_model->set($storefront, $value);
				}
			}
		}
	}

	public function saveThemeTemplates($app, $theme_templates)
	{
		foreach ($theme_templates as $theme_id => $settings)
		{
			$template_storage_factory = new shopBreadcrumbsAppTemplateStorageFactory();
			$template_storage = $template_storage_factory->getAppThemeTemplateStorage($app, $theme_id);

			if ($template_storage)
			{
				$template_storage->storeSettings($settings);
			}
		}
	}

	private function saveBlogSettings($blog_settings)
	{
		$blog_settings_model = new shopBreadcrumbsBlogSettingsModel();
		$settings_fields = $blog_settings_model->availableSettings();

		foreach ($settings_fields as $option)
		{
			if (array_key_exists($option, $blog_settings))
			{
				$blog_settings_model->set($option, $blog_settings[$option]);
			}
		}
	}
}