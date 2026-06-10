<?php

$model = new waModel();

try
{
	$model->query('SELECT `context` FROM `shop_seofilter_storefront_fields_values` LIMIT 1');
}
catch (Exception $e)
{
	$queries = array();

	$queries[] = '
ALTER TABLE `shop_seofilter_storefront_fields_values`
	ADD COLUMN `context` ENUM(\'DEFAULT\',\'PAGINATION\') NOT NULL DEFAULT \'DEFAULT\' AFTER `value`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`storefront`, `field_id`, `context`);
';

	$queries[] = '
CREATE TABLE `shop_seofilter_generator_history_feature_value` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`generator_history_feature_id` INT(11) UNSIGNED NOT NULL,
	`value_id` INT(11) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `generator_history_feature_id` (`generator_history_feature_id`)
)
COLLATE=\'utf8_general_ci\'
';

	$queries[] = '
CREATE TABLE `shop_seofilter_default_template_settings` (
	`storefront` VARCHAR(255) NOT NULL,
	`name` VARCHAR(75) NOT NULL,
	`value` TEXT NOT NULL,
	PRIMARY KEY (`storefront`, `name`)
)
COLLATE=\'utf8_general_ci\'
';

	$queries[] = '
ALTER TABLE `shop_seofilter_filter_personal_rule`
	ADD COLUMN `is_pagination_templates_enabled` TINYINT UNSIGNED NOT NULL DEFAULT \'0\' AFTER `categories_use_mode`,
	ADD COLUMN `seo_h1_pagination` TEXT NOT NULL AFTER `is_pagination_templates_enabled`,
	ADD COLUMN `seo_description_pagination` TEXT NOT NULL AFTER `seo_h1_pagination`,
	ADD COLUMN `meta_title_pagination` TEXT NOT NULL AFTER `seo_description_pagination`,
	ADD COLUMN `meta_description_pagination` TEXT NOT NULL AFTER `meta_title_pagination`,
	ADD COLUMN `meta_keywords_pagination` TEXT NOT NULL AFTER `meta_description_pagination`,
	ADD COLUMN `additional_description` TEXT NOT NULL AFTER `meta_keywords`,
	ADD COLUMN `additional_description_pagination` TEXT NOT NULL AFTER `meta_keywords_pagination`;
';

	foreach ($queries as $sql)
	{
		$model->exec($sql);
	}
}