<?php

$model = new waModel();

$get_all_sql = 'SELECT *
FROM shop_breadcrumbs_settings';

$delete_sql = '
DELETE FROM shop_breadcrumbs_settings
WHERE storefront = :storefront AND `name` IN (s:names)
';

$insert_sql = '
INSERT IGNORE INTO shop_breadcrumbs_settings
(`storefront`, `name`, `value`)
VALUES (:storefront, :name, :value)
';

$all_settings = $model->query($get_all_sql)->fetchAll();
$all_settings_by_storefront = array();

foreach ($all_settings as $setting)
{
	if (!array_key_exists($setting['storefront'], $all_settings_by_storefront))
	{
		$all_settings_by_storefront[$setting['storefront']] = array();
	}

	$all_settings_by_storefront[$setting['storefront']][$setting['name']] = $setting['value'];
}

foreach ($all_settings_by_storefront as $storefront => $settings)
{
	if (array_key_exists('hide_current_item', $settings) && array_key_exists('show_current_item_link', $settings))
	{
		if ($settings['hide_current_item'] == '0')
		{
			$current_level_mode = 'NONE';
		}
		elseif ($settings['show_current_item_link'] == '0')
		{
			$current_level_mode = 'SHOW';
		}
		else
		{
			$current_level_mode = 'SHOW_AS_LINK';
		}

		$delete_params = array(
			'storefront' => $storefront,
			'names' => array('hide_current_item', 'show_current_item_link'),
		);
		$model->exec($delete_sql, $delete_params);

		$insert_params = array(
			'storefront' => $storefront,
			'name' => 'current_level_mode',
			'value' => $current_level_mode,
		);
		$model->exec($insert_sql, $insert_params);
	}



	if (array_key_exists('show_subcategories', $settings) && array_key_exists('show_subcategories_on_hover', $settings))
	{
		if ($settings['show_subcategories'] == '0')
		{
			$categories_menu_mode = 'NONE';
		}
		elseif ($settings['show_subcategories_on_hover'] == '0')
		{
			$categories_menu_mode = 'SHOW_ON_CLICK';
		}
		else
		{
			$categories_menu_mode = 'SHOW_ON_HOVER';
		}

		$delete_params = array(
			'storefront' => $storefront,
			'names' => array('show_subcategories', 'show_subcategories_on_hover'),
		);
		$model->exec($delete_sql, $delete_params);

		$insert_params = array(
			'storefront' => $storefront,
			'name' => 'categories_menu_mode',
			'value' => $categories_menu_mode,
		);
		$model->exec($insert_sql, $insert_params);
	}


	$insert_params = array(
		'storefront' => $storefront,
		'name' => 'category_name_mode',
		'value' => 'SEO_H1',
	);
	$model->exec($insert_sql, $insert_params);
}