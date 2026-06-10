<?php

$model = new waModel();

try
{
	$model->exec('SELECT `default_product_sort` FROM `shop_seofilter_filter` LIMIT 1');
}
catch (Exception $e)
{
	$create_column_sqls = array();

	$create_column_sqls[] = '
ALTER TABLE `shop_seofilter_filter`
	ADD COLUMN `default_product_sort` VARCHAR(32) NOT NULL DEFAULT \'\' AFTER `feature_value_ranges_count`;
';

	$create_column_sqls[] = '
ALTER TABLE `shop_seofilter_filter_personal_rule`
	ADD COLUMN `default_product_sort` VARCHAR(32) NOT NULL DEFAULT \'\' AFTER `is_enabled`;
';

	foreach ($create_column_sqls as $create_column_sql)
	{
		$model->exec($create_column_sql);
	}
}