<?php

class shopBreadcrumbsViewHelper
{
	private static $already_rendered = false;

	public static function getBreadcrumbs()
	{
		if (self::$already_rendered)
		{
			return '';
		}



		$helper = new shopBreadcrumbsHelper();

		$app = waSystem::getApp();

		$settings_factory = new shopBreadcrumbsSettingsFactory();
		$settings = $settings_factory->getAppSettings($app);

		$view = wa()->getView();
		$theme = self::getCurrentTheme();

		$template_storage_factory = new shopBreadcrumbsAppTemplateStorageFactory();
		$template_storage = $template_storage_factory->getAppThemeTemplateStorage($app, $theme);

		if (!$template_storage || !$theme)
		{
			return '';
		}

		$home_name = $template_storage->getHomeName();
		$breadcrumbs = new shopBreadcrumbsBreadcrumbs($settings, $home_name);

		$view_vars = array(
			'storefront' => shopBreadcrumbsPlugin::getStorefront(),
			'breadcrumbs' => $breadcrumbs->getPath(),
			'current_page_item' => $breadcrumbs->getCurrentPageItem(),
			'hide_current_item' => $settings->hide_current_item,
			'show_current_item_link' => $settings->show_current_item_link,
			'show_subcategories' => $settings instanceof shopBreadcrumbsSettings ? $settings->show_subcategories : false,
			'show_subcategories_on_hover' => $settings instanceof shopBreadcrumbsSettings ? $settings->show_subcategories_on_hover : false,
			'css_url' => $template_storage->getCssUrl() . '?v=' . (waSystemConfig::isDebug() ? time() : $template_storage->getCssAssetVersion()),
			'separator' => $template_storage->getSeparator(),
			'home_name' => $home_name,
			'js_url' => shopBreadcrumbsPlugin::getStaticUrl('js/breadcrumbs.js') . '?v=' . (waSystemConfig::isDebug() ? time() : $helper->getPluginVersion()),
		);
		
		// $view_vars['current_page_item']['name'] = 111;
		
		$view->assign('breadcrumbs_plugin', $view_vars);

		self::assignLegacyVariables($breadcrumbs, $view, $template_storage);

		self::$already_rendered = true;

		return $view->fetch($template_storage->getTemplatePath());
	}

	private static function assignLegacyVariables(shopBreadcrumbsBreadcrumbs $breadcrumbs, waSmarty3View $view, shopBreadcrumbsTemplateStorage $template_storage)
	{
		$settings = new shopBreadcrumbsSettings();

		$breadcrumbs_array = $breadcrumbs->getPath();
		if (!is_array($breadcrumbs_array))
		{
			$breadcrumbs_array = array();
		}

		if (count($breadcrumbs_array))
		{
			array_shift($breadcrumbs_array);
		}

		foreach ($breadcrumbs_array as &$breadcrumb)
		{
			if (isset($breadcrumb['brothers']))
			{
				$breadcrumb['childs'] = $breadcrumb['brothers'];
				if (is_array($breadcrumb['childs']))
				{
					foreach ($breadcrumb['childs'] as &$child)
					{
						$child['url'] = $child['frontend_url'];
					}
					unset($child);
				}
				unset($breadcrumb['brothers']);
			}
		}
		unset($breadcrumb);

		$view->assign(array(
			'url' => shopBreadcrumbsPlugin::getStaticUrl(''),
			'style' => "<style type='text/css'>" . file_get_contents($template_storage->getCssPath()) . "</style>",
			'hover' => $settings->show_subcategories_on_hover,
			'breadcrumbs' => $breadcrumbs_array,
		));
	}

	/**
	 * @return null|waTheme
	 */
	private static function getCurrentTheme()
	{
		$theme_id = waRequest::getTheme();
		$themes = wa()->getThemes(waSystem::getApp());

		return array_key_exists($theme_id, $themes)
			? $themes[$theme_id]
			: null;
	}
}