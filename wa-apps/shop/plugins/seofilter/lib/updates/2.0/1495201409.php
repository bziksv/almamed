<?php

try
{
	$model = new waModel();
	$model->exec('
ALTER TABLE `shop_seofilter_filter_personal_rule`
	CHANGE COLUMN `meta_title` `meta_title` TEXT NOT NULL AFTER `seo_description`;

');
}
catch (Exception $e)
{}