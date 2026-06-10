<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM shop_seofilter_sitemap_cache_queue LIMIT 1');
}
catch (Exception $e)
{
	$sqls = array();

	$sqls[] = '
CREATE TABLE `shop_seofilter_sitemap_cache_queue` (
	`storefront` VARCHAR(255) NOT NULL,
	`category_id` INT(10) UNSIGNED NOT NULL,
	`filter_ids` TEXT NOT NULL,
	`refresh_after` INT(11) NOT NULL,
	`domain` VARCHAR(255) NOT NULL,
	`shop_url` VARCHAR(255) NOT NULL,
	`lock_timestamp` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`storefront`, `category_id`),
	INDEX `refresh_after_lock_timestamp` (`refresh_after`, `lock_timestamp`),
	INDEX `category_id_lock_timestamp` (`category_id`, `lock_timestamp`),
	INDEX `category_id` (`category_id`)
)
COLLATE=\'utf8_general_ci\'
';

	$sqls[] = '
ALTER TABLE `shop_seofilter_sitemap_cache`
	DROP INDEX `category_id_locked`,
	DROP INDEX `refresh_after_locked`,
	DROP INDEX `refresh_after`,
	DROP INDEX `storefront`
';

	$sqls[] = '
ALTER TABLE `shop_seofilter_sitemap_cache`
	DROP COLUMN `refresh_after`,
	DROP COLUMN `domain_id`,
	DROP COLUMN `shop_url`,
	DROP COLUMN `locked`
';

	foreach ($sqls as $sql)
	{
		$model->exec($sql);
	}
}