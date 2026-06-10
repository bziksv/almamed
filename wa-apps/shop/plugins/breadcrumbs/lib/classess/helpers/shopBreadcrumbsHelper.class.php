<?php

class shopBreadcrumbsHelper
{
	public function isSeofilterPluginEnabled()
	{
		$info = wa('shop')->getConfig()->getPluginInfo('seofilter');
		$version = ifset($info['version']);

		if ($info === array() || !$version || version_compare($version, '2.6', '<'))
		{
			return false;
		}

		if (!class_exists('shopSeofilterPluginSettings') || !class_exists('shopSeofilterFiltersStorage'))
		{
			return false;
		}

		$settings = shopSeofilterBasicSettingsModel::getSettings();

		return $settings->is_enabled;
	}

	public function isBrandPluginEnabled()
	{
		$info = wa('shop')->getConfig()->getPluginInfo('brand');

		if ($info === array() || !class_exists('shopBrandSettings') || !class_exists('shopBrandSettingsStorage'))
		{
			return false;
		}

		$storage = new shopBrandSettingsStorage();
		$settings = $storage->getSettings();

		return $settings->is_enabled;
	}

	public function isReviewsplusEnabled()
	{
		$info = wa('shop')->getConfig()->getPluginInfo('reviewsplus');

		if ($info === array() || !class_exists('shopReviewsplusPlugin'))
		{
			return false;
		}

		$settings = shopReviewsplusPlugin::getPluginSettings();

		return isset($settings['state']) || $settings['state'] == 1;
	}

	public function getTheme($app, $theme)
	{
		if ($theme instanceof waTheme)
		{
			return $theme;
		}
		else
		{
			$theme_id = $theme;

			$themes = wa()->getThemes($app);

			return array_key_exists($theme_id, $themes)
				? $themes[$theme_id]
				: null;
		}
	}

	public function isAppInstalled($app)
	{
		try
		{
			wa($app);
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}

	public function getPluginVersion()
	{
		$info = wa('shop')->getConfig()->getPluginInfo('breadcrumbs');

		return $info['version'];
	}
}