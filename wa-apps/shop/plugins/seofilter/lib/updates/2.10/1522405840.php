<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM `shop_seofilter_filter_personal_canonical` LIMIT 1');
}
catch (Exception $e)
{
	$create_sqls = array();

	$create_sqls[] = '
CREATE TABLE `shop_seofilter_filter_personal_canonical` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`filter_id` INT(10) UNSIGNED NOT NULL,
	`is_enabled` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'1\',
	`storefronts_use_mode` ENUM(\'ALL\',\'EXCEPT\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	`categories_use_mode` ENUM(\'ALL\',\'EXCEPT\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	`canonical_url_template` VARCHAR(2048) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `filter_id` (`filter_id`),
	INDEX `filter_id_is_enabled` (`filter_id`, `is_enabled`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$create_sqls[] = '
CREATE TABLE `shop_seofilter_filter_personal_canonical_category` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`canonical_id` INT(10) UNSIGNED NOT NULL,
	`category_id` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `canonical_id` (`canonical_id`),
	INDEX `category_id` (`category_id`),
	INDEX `canonical_id_category_id` (`canonical_id`, `category_id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$create_sqls[] = '
CREATE TABLE `shop_seofilter_filter_personal_canonical_storefront` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`canonical_id` INT(10) UNSIGNED NOT NULL,
	`storefront` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `canonical_id` (`canonical_id`),
	INDEX `storefront` (`storefront`),
	INDEX `canonical_id_storefront` (`canonical_id`, `storefront`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	foreach ($create_sqls as $create_sql)
	{
		$model->exec($create_sql);
	}
}

$cleaner = new shopSeofilterCleaner();
$cleaner->clean();