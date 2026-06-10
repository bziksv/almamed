<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM `shop_breadcrumbs_settings` LIMIT 1');
}
catch (Exception $e)
{
	$queries[] = '
CREATE TABLE `shop_breadcrumbs_settings` (
	`storefront` VARCHAR(255) NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`storefront`, `name`),
	INDEX `storefront` (`storefront`)
)
COLLATE=\'utf8_general_ci\'
';

	foreach ($queries as $sql)
	{
		$model->exec($sql);
	}


	// перенос настроек
	$settings_insert_query = '
INSERT INTO `shop_breadcrumbs_settings`
(`storefront`, `name`, `value`)
VALUES (\'*\', :name, :value)
';
	$get_settings_sql = '
SELECT `name`, `value`
FROM `wa_app_settings` 
WHERE app_id = \'shop.breadcrumbs\'
';

	$drop_settings = array();
	foreach ($model->query($get_settings_sql) as $settings_row)
	{
		$settings_name = $settings_row['name'];
		$settings_value = $settings_row['value'];

		$insert_params = null;
		if ($settings_name == 'hover')
		{
			$insert_params = array(
				'name' => 'show_subcategories',
				'value' => '1',
			);
			$model->exec($settings_insert_query, $insert_params);

			$insert_params = array(
				'name' => 'show_subcategories_on_hover',
				'value' => $settings_value,
			);
			$model->exec($settings_insert_query, $insert_params);

			$drop_settings[] = $settings_name;
		}
		elseif ($settings_name == 'style')
		{
			$style_path = wa('shop')->getDataPath('plugins/breadcrumbs/' . md5('*') . '/breadcrumbs.css', true, 'shop', false);
			waFiles::write($style_path, $settings_value);

			$drop_settings[] = $settings_name;
		}
		elseif ($settings_name == 'template')
		{
			$template_path = wa('shop')->getDataPath('plugins/breadcrumbs/' . md5('*') . '/Breadcrumbs.html', false, 'shop', false);
			waFiles::write($template_path, $settings_value);

			$drop_settings[] = $settings_name;
		}
	}

	if (count($drop_settings))
	{
		$drop_settings_sql = '
DELETE
FROM `wa_app_settings`
WHERE
	app_id = \'shop.breadcrumbs\'
	AND `name` IN (s:fields)
';

		$model->exec($drop_settings_sql, array('fields' => $drop_settings));
	}
}