<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 from `shop_breadcrumbs_seofilter_feature` LIMIT 1');
}
catch (Exception $e)
{
	$sql = '
CREATE TABLE `shop_breadcrumbs_seofilter_feature` (
	`storefront` VARCHAR(255) NOT NULL,
	`feature_id` INT UNSIGNED NOT NULL,
	`sort` INT UNSIGNED NOT NULL,
	INDEX `storefront` (`storefront`),
	PRIMARY KEY (`storefront`, `feature_id`)
)
COLLATE=\'utf8_general_ci\'
';

	$model->exec($sql);
}