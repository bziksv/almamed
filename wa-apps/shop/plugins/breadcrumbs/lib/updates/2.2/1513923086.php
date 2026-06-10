<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM `shop_breadcrumbs_theme_settings` LIMIT 1');
}
catch (Exception $e)
{
	$create_sql = '
CREATE TABLE `shop_breadcrumbs_theme_settings` (
	`theme_id` CHAR(255) NOT NULL,
	`name` CHAR(64) NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`theme_id`, `name`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$model->exec($create_sql);
}