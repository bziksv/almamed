<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM `shop_seofilter_filter_tree_category_settings` LIMIT 1');
}
catch (Exception $e)
{
	$create_table_queries = array();

	$create_table_queries[] = '
CREATE TABLE `shop_seofilter_filter_tree_category_settings` (
	`storefront` VARCHAR(255) NOT NULL,
	`category_id` INT(10) UNSIGNED NOT NULL,
	`enabled_for_storefronts` ENUM(\'ALL\',\'NONE\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	PRIMARY KEY (`storefront`, `category_id`),
	INDEX `category_id` (`category_id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$create_table_queries[] = '
CREATE TABLE `shop_seofilter_filter_tree_category_feature_settings` (
	`storefront` VARCHAR(255) NOT NULL,
	`category_id` INT(10) UNSIGNED NOT NULL,
	`feature_id` INT(10) UNSIGNED NOT NULL,
	`enabled_for_storefronts` ENUM(\'ALL\',\'NONE\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	PRIMARY KEY (`storefront`, `category_id`, `feature_id`),
	INDEX `category_id_feature_id` (`category_id`, `feature_id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	foreach ($create_table_queries as $sql)
	{
		$model->exec($sql);
	}
}