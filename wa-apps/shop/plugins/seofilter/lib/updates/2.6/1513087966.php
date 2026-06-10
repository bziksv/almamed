<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM shop_seofilter_filter_field LIMIT 1');
}
catch (Exception $e)
{
	$create_queries = array();

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_field` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`sort` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';
	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_field_value` (
	`filter_id` INT(11) UNSIGNED NOT NULL,
	`field_id` INT(11) UNSIGNED NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`filter_id`, `field_id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	foreach ($create_queries as $create_query)
	{
		$model->exec($create_query);
	}
}