<?php

$routing = wa('shop')->getRouting();
$domains = $routing->getByApp('shop');

$themes = wa('shop')->getThemes('shop');

$themes_without_templates = array();
$themes_without_css = array();
foreach (array_keys($themes) as $theme_id)
{
	$themes_without_templates[$theme_id] = $theme_id;
	$themes_without_css[$theme_id] = $theme_id;
}

$show_old_templates_in_settings = false;
foreach ($domains as $domain => $domain_routes)
{
	foreach ($domain_routes as $route)
	{
		$storefront = $domain . '/' . ifset($route['url']);

		$old_css = wa('shop')->getDataPath('plugins/breadcrumbs/', true, 'shop') . md5($storefront) . '/breadcrumbs.css';
		$old_template = wa('shop')->getDataPath('plugins/breadcrumbs/', false, 'shop') . md5($storefront) . '/Breadcrumbs.html';

		$theme_ids = array(
			ifset($route['theme']),
			ifset($route['theme_mobile']),
		);
		$theme_ids = array_filter($theme_ids);
		$theme_ids = array_unique($theme_ids);

		foreach ($theme_ids as $theme_id)
		{
			if (!$theme_id || !array_key_exists($theme_id, $themes))
			{
				continue;
			}

			$theme = $themes[$theme_id];

			if (file_exists($old_css))
			{
				$show_old_templates_in_settings = true;

				$theme_css_data = $theme->getFile('breadcrumbs_plugin.css');
				if (count($theme_css_data) == 0)
				{
					$theme->addFile('breadcrumbs_plugin.css', "Плагин \"Навигация в хлебных крошках\" (стили витрины {$storefront})");
					waFiles::copy($old_css, $theme->getPath() . '/' . 'breadcrumbs_plugin.css');
					unset($themes_without_css[$theme_id]);
				}
			}

			if (file_exists($old_template))
			{
				$show_old_templates_in_settings = true;

				$theme_template_data = $theme->getFile('breadcrumbs_plugin.html');
				if (count($theme_template_data) == 0)
				{
					$theme->addFile('breadcrumbs_plugin.html', "Плагин \"Навигация в хлебных крошках\" (шаблон витрины {$storefront})");
					waFiles::copy($old_template, $theme->getPath() . '/' . 'breadcrumbs_plugin.html');
					unset($themes_without_templates[$theme_id]);
				}
			}
		}
	}
}


$app_settings_model = new waAppSettingsModel();
$app_settings_model->set('shop.breadcrumbs', 'show_old_templates_in_settings', $show_old_templates_in_settings ? '1' : '0');



$general_old_css = wa('shop')->getDataPath('plugins/breadcrumbs/', true, 'shop') . md5('*') . '/breadcrumbs.css';
$general_old_template = wa('shop')->getDataPath('plugins/breadcrumbs/', false, 'shop') . md5('*') . '/Breadcrumbs.html';

if (file_exists($general_old_css))
{
	foreach ($themes_without_css as $theme_id)
	{
		$theme = $themes[$theme_id];

		$theme->addFile('breadcrumbs_plugin.css', "Плагин \"Навигация в хлебных крошках\" (общие стили)");
		waFiles::copy($general_old_css, $theme->getPath() . '/' . 'breadcrumbs_plugin.css');
	}
}

if (file_exists($general_old_template))
{
	foreach ($themes_without_templates as $theme_id)
	{
		$theme = $themes[$theme_id];

		$theme->addFile('breadcrumbs_plugin.html', "Плагин \"Навигация в хлебных крошках\" (общий шаблон)");
		waFiles::copy($general_old_template, $theme->getPath() . '/' . 'breadcrumbs_plugin.html');
	}
}
