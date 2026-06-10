<?php

$model = new waModel();

try
{
	$model->exec('SELECT empty_page_http_code FROM shop_seofilter_filter LIMIT 1');
}
catch (Exception $e)
{
	$model->exec('
ALTER TABLE `shop_seofilter_filter`
	ADD COLUMN `empty_page_http_code` VARCHAR(5) NULL DEFAULT NULL AFTER `default_product_sort`;
');
}
