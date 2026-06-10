<?php

class shopBreadcrumbsPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$seo_helper = new shopBreadcrumbsSeoHelper();
		$helper = new shopBreadcrumbsHelper();
		$template_storage_factory = new shopBreadcrumbsAppTemplateStorageFactory();
		$template_storage = $template_storage_factory->getAppThemeTemplateStorage('shop');

		$storefronts = $this->getStorefronts();

		$this->view->assign('storefront_settings', $this->prepareStorefrontSettings());
		$this->view->assign('theme_templates', $this->prepareAppThemeTemplates('shop'));
		$this->view->assign('blog_settings', $this->prepareBlogSettings());
		$this->view->assign('blog_theme_templates', $this->prepareAppThemeTemplates('blog'));
		$this->view->assign('old_templates', $this->prepareOldTemplates($storefronts));
		$this->view->assign('blog_is_installed', $helper->isAppInstalled('blog'));

		$this->view->assign('storefronts', $storefronts);
		$this->view->assign('themes', $this->getThemes('shop'));
		$this->view->assign('blog_themes', $this->getThemes('blog'));
		$this->view->assign('features', $this->getFeatures());
		$this->view->assign('default_style', file_get_contents($template_storage->getPluginCssPath()));
		$this->view->assign('default_template', file_get_contents($template_storage->getPluginTemplatePath()));
		$this->view->assign('is_seofilter_plugin_enabled', $helper->isSeofilterPluginEnabled());
		$this->view->assign('is_seo_plugin_enabled', $seo_helper->isPluginEnabled());

		$this->view->assign('asset_version', waSystemConfig::isDebug() ? time() : $helper->getPluginVersion());
	}

	private function prepareStorefrontSettings()
	{
		$settings = new shopBreadcrumbsSettings(shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL);

		return array(shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL => $settings->getRawSettings());
	}

	private function prepareAppThemeTemplates($app)
	{
		$helper = new shopBreadcrumbsHelper();
		if (!$helper->isAppInstalled($app))
		{
			return array();
		}

		/** @var waTheme[] $themes */
		$themes = wa()->getThemes($app);

		$theme = reset($themes);
		if (!$theme)
		{
			return array();
		}

		$template_storage_factory = new shopBreadcrumbsAppTemplateStorageFactory();
		$template_storage = $template_storage_factory->getAppThemeTemplateStorage($app, $theme);

		return array($theme->id => $template_storage ? $template_storage->getTemplateSettings() : null);
	}

	private function prepareOldTemplates($storefronts)
	{
		$settings = new shopBreadcrumbsSettings();
		if (!$settings->show_old_templates_in_settings)
		{
			return array();
		}

		$helper = new shopBreadcrumbsOldStorefrontTemplateHelper();

		$old_templates = array();

		$general_templates = $helper->getStorefrontTemplates(shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL);
		if (count($general_templates))
		{
			$old_templates[shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL] = $general_templates;
		}

		foreach ($storefronts as $storefront_data)
		{
			$storefront = $storefront_data['storefront'];

			$storefront_templates = $helper->getStorefrontTemplates($storefront);
			if (count($storefront_templates))
			{
				$old_templates[$storefront] = $storefront_templates;
			}
		}

		return $old_templates;
	}

	private function getThemes($app)
	{
		$themes = array();

		$theme_settings_model = new shopBreadcrumbsThemeSettingsModel();
		$modified_themes = array();
		foreach ($theme_settings_model->select('DISTINCT theme_id')->query() as $row)
		{
			$modified_themes[$row['theme_id']] = true;
		}

		foreach (wa()->getThemes($app) as $theme)
		{
			$template_storage_factory = new shopBreadcrumbsAppTemplateStorageFactory();
			$template_storage = $template_storage_factory->getAppThemeTemplateStorage($app, $theme);

			$themes[] = array(
				'id' => $theme->id,
				'name' => $theme->getName(),
				'is_modified' => array_key_exists($theme->id, $modified_themes) || $template_storage->hasCustomTemplates(),
			);
		}

		return $themes;
	}

	private function getStorefronts()
	{
		$storefronts = array();

		$settings_model = new shopBreadcrumbsSettingsModel();
		$modified_storefronts = array();
		foreach ($settings_model->select('DISTINCT storefront')->query() as $row)
		{
			$modified_storefronts[$row['storefront']] = true;
		}

		$seofilter_feature_model = new shopBreadcrumbsSeofilterFeatureModel();
		foreach ($seofilter_feature_model->select('DISTINCT storefront')->query() as $row)
		{
			$modified_storefronts[$row['storefront']] = true;
		}

		$storefronts[] = array(
			'storefront' => shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL,
			'name' => 'Все витрины',
			'is_modified' => isset($modified_storefronts[shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL]),
		);

		$routing = wa()->getRouting();
		$domains = $routing->getByApp('shop');

		$is_idna_installed = class_exists('waIdna') && method_exists('waIdna', 'dec');

		foreach ($domains as $domain => $domain_routes)
		{
			foreach ($domain_routes as $route)
			{
				$storefront = $domain . '/' . ifset($route['url']);
				$storefronts[] = array(
					'storefront' => $storefront,
					'name' => $is_idna_installed ? waIdna::dec($storefront) : $storefront,
					'is_modified' => isset($modified_storefronts[$storefront]),
				);
			}
		}

		return $storefronts;
	}

	private function getFeatures()
	{
		$feature_model = new shopFeatureModel();

		return $feature_model->select('id,name,code')->fetchAll('id');
	}

	private function prepareBlogSettings()
	{
		$settings = new shopBreadcrumbsBlogSettings();

		return $settings->getRawSettings();
	}
}