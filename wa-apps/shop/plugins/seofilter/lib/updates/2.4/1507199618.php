<?php

$model = new waModel();

$sql = '
SELECT storefront, category_id, filter_ids
FROM shop_seofilter_sitemap_cache_queue
WHERE filter_ids <> \'\'
';

$update_sql = '
UPDATE shop_seofilter_sitemap_cache_queue
SET filter_ids = :filter_ids
WHERE storefront = :storefront AND category_id = :category_id
';

foreach ($model->query($sql) as $row)
{
	$ids = unserialize($row['filter_ids']);
	if (is_array($ids))
	{
		$params = array(
			'storefront' => $row['storefront'],
			'category_id' => $row['category_id'],
			'filter_ids' => implode(',', $ids),
		);

		$model->query($update_sql, $params);
	}
}



$sql = '
SELECT storefront, category_id, filter_ids
FROM shop_seofilter_sitemap_cache
WHERE filter_ids <> \'\'
';

$update_sql = '
UPDATE shop_seofilter_sitemap_cache
SET filter_ids = :filter_ids
WHERE storefront = :storefront AND category_id = :category_id
';

foreach ($model->query($sql) as $row)
{
	$ids = unserialize($row['filter_ids']);
	if (is_array($ids))
	{
		$params = array(
			'storefront' => $row['storefront'],
			'category_id' => $row['category_id'],
			'filter_ids' => implode(',', $ids),
		);

		$model->query($update_sql, $params);
	}
}