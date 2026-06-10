<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM shop_seofilter_productfilters_category_feature_rule LIMIT 1');
}
catch (Exception $e)
{
	$create_sql_queries = array();

	$create_sql_queries[] = '
CREATE TABLE `shop_seofilter_productfilters_category_feature_rule` (
	`storefront` VARCHAR(255) NOT NULL,
	`category_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	`feature_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	`link_category_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	`display_link` ENUM(\'Y\',\'N\') NULL DEFAULT \'Y\',
	UNIQUE INDEX `storefront_category_id_feature_id` (`storefront`, `category_id`, `feature_id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$create_sql_queries[] = '
CREATE TABLE `shop_seofilter_productfilters_category_settings` (
	`storefront` VARCHAR(255) NOT NULL,
	`category_id` INT(10) UNSIGNED NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NOT NULL,
	PRIMARY KEY (`storefront`, `category_id`, `name`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;

';

	$create_sql_queries[] = '
CREATE TABLE `shop_seofilter_productfilters_settings` (
	`storefront` VARCHAR(255) NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NOT NULL,
	PRIMARY KEY (`storefront`, `name`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;

';

	foreach ($create_sql_queries as $create_query)
	{
		$model->exec($create_query);
	}
}