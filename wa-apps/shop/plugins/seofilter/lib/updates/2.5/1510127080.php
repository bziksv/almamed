<?php

$model = new waModel();

try
{
	$model->exec('SELECT filter_ids_with_single_value FROM shop_seofilter_sitemap_cache_queue LIMIT 1');
}
catch (Exception $e)
{
	$alter_sql = '
ALTER TABLE `shop_seofilter_sitemap_cache_queue`
	ADD COLUMN `filter_ids_with_single_value` TEXT NOT NULL AFTER `filter_ids`;
';

	$model->exec($alter_sql);

	$select_sql = '
SELECT fv.filter_id
FROM shop_seofilter_filter_feature_value AS fv
	LEFT JOIN shop_seofilter_filter_feature_value_range AS fvr
		ON fvr.filter_id = fv.filter_id
WHERE fvr.id IS NULL
GROUP BY fv.filter_id
HAVING COUNT(fv.filter_id) = 1
';

	$all_single_value_filter_ids = array();
	foreach ($model->query($select_sql)->fetchAll() as $row)
	{
		$all_single_value_filter_ids[$row['filter_id']] = $row['filter_id'];
	}

	$cache_queue_select_sql = '
SELECT *
FROM shop_seofilter_sitemap_cache_queue
WHERE lock_timestamp IS NULL AND filter_ids <> \'\'
';

	$cache_queue_update_sql = '
UPDATE shop_seofilter_sitemap_cache_queue
SET
	filter_ids = :filter_ids,
	filter_ids_with_single_value = :filter_ids_with_single_value
WHERE storefront = :storefront AND category_id = :category_id
';

	foreach ($model->query($cache_queue_select_sql) as $queue_row)
	{
		$filter_ids = explode(',', $queue_row['filter_ids']);
		if (!count($filter_ids))
		{
			continue;
		}

		$single_value_filter_ids = array();
		$other_filter_ids = array();

		foreach ($filter_ids as $filter_id)
		{
			if (isset($all_single_value_filter_ids[$filter_id]))
			{
				$single_value_filter_ids[] = $filter_id;
			}
			else
			{
				$other_filter_ids[] = $filter_id;
			}
		}


		$update_params = array(
			'storefront' => $queue_row['storefront'],
			'category_id' => $queue_row['category_id'],
			'filter_ids_with_single_value' => implode(',', $single_value_filter_ids),
			'filter_ids' => implode(',', $other_filter_ids),
		);

		$model->exec($cache_queue_update_sql, $update_params);
	}
}