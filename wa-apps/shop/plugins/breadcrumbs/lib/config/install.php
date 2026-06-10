<?php

try
{
	$model = new waModel();
	$sql = '
	ALTER TABLE `shop_breadcrumbs_theme_settings`
		COLLATE=\'utf8mb4_general_ci\',
		CONVERT TO CHARSET utf8mb4;
	';
	$model->exec($sql);

	$settings_model = new waAppSettingsModel();
	$settings_model->set('shop.breadcrumbs', 'use_theme_settings_utf8mb4', 1);
}
catch (Exception $e)
{
}