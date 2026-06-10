<?php


$model = new waModel();

$index_exists = count($model->query('SHOW INDEX FROM `shop_seofilter_sitemap_cache` WHERE Key_name=\'category_id_locked\'')->fetchAll()) > 0;
if (!$index_exists)
{
	$sql = '
ALTER TABLE `shop_seofilter_sitemap_cache`
	ADD INDEX `category_id_locked` (`category_id`, `locked`);
';
	$model->exec($sql);
}