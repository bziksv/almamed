<?php

$model = new waModel();
try
{
	$model->exec('SELECT `feature_values_count` FROM `shop_seofilter_filter`');
}
catch (Exception $e)
{
	$sql = '
ALTER TABLE `shop_seofilter_filter`
	ADD COLUMN `feature_values_count` TINYINT UNSIGNED NOT NULL,
	ADD COLUMN `feature_value_ranges_count` TINYINT UNSIGNED NOT NULL,
	ADD INDEX `feature_values_count` (`feature_values_count`);
';

	$model->exec($sql);

	$query = $model->query('SELECT `id` AS `filter_id` FROM `shop_seofilter_filter`');
	$update_sql = '
UPDATE `shop_seofilter_filter`
SET `feature_values_count` = (SELECT COUNT(*) FROM `shop_seofilter_filter_feature_value` WHERE `filter_id` = :filter_id),
`feature_value_ranges_count` = (SELECT COUNT(*) FROM `shop_seofilter_filter_feature_value_range` WHERE `filter_id` = :filter_id)
WHERE `id` = :filter_id;
';

	foreach ($query as $filter_params)
	{
		$model->exec($update_sql, $filter_params);
	}
}