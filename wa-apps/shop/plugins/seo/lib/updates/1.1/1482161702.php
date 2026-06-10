<?php

$model = new waModel();

try
{
	$result = $model->query(
		'SELECT `storefront`, REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(`page`,\'_\', `name`), \'category_\', \'categories_\'), \'product_\', \'products_\'), \'brand_\', \'brands_\'), \'tag_\', \'tags_\') as `name`, `value` FROM `shop_seo_settings`'
	)->fetchAll();
	$model->query('DROP TABLE IF EXISTS `shop_seo_settings`');
	$model->query(
		'
CREATE TABLE IF NOT EXISTS `shop_seo_settings` (
  `storefront` varchar(255) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` text,
  PRIMARY KEY (`storefront`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
	);

	foreach ($result as $r)
	{
		$model->query(
			'INSERT INTO `shop_seo_settings` VALUES(\'' . $model->escape(
				$r['storefront']
			) . '\',\'' . $model->escape(
				$r['name']
			) . '\',\'' . $model->escape($r['value']) . '\')'
		);
	}

	if ($model->query('SELECT * FROM `shop_seo_settings`')->count() > 0)
	{
		$model->query(
			'INSERT INTO `shop_seo_settings` VALUES(\'general\',\'plugin_enable\',\'1\')'
		);
	}
}
catch (waDbException $e)
{

}

try
{
	$result = $model->query(
		'SELECT `category_id`, `storefront`, REPLACE(REPLACE(CONCAT(`page`,\'_\', `name`), \'subcategory_\', \'subcategories_\'), \'product_\', \'products_\') as `name`, `value` FROM `shop_seo_settings_category`'
	)->fetchAll();
	$model->query('DROP TABLE IF EXISTS `shop_seo_settings_category`');
	$model->query(
		'
CREATE TABLE IF NOT EXISTS `shop_seo_settings_category` (
  `category_id` int(11) NOT NULL DEFAULT \'0\',
  `storefront` varchar(255) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` text,
  PRIMARY KEY (`category_id`,`storefront`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
	);

	foreach ($result as $r)
	{
		$model->query(
			'INSERT INTO `shop_seo_settings_category` VALUES(\''
			. $model->escape($r['category_id'])
			. '\', \''
			. $model->escape($r['storefront'])
			. '\',\''
			. $model->escape($r['name'])
			. '\',\''
			. $model->escape($r['value'])
			. '\')'
		);
	}

	if ($model->query(
			'SELECT * FROM `shop_seo_settings_category` WHERE `name` = \'subcategories_enable\' AND `value` = \'1\''
		)->count() > 0
	)
	{
		$model->query(
			'REPLACE INTO `shop_seo_settings` VALUES(\'general\',\'category_subcategories_enable\',\'1\')'
		);
	}

	if ($model->query(
			'SELECT * FROM `shop_seo_settings_category` WHERE `name` = \'products_enable\' AND `value` = \'1\''
		)->count() > 0
	)
	{
		$model->query(
			'REPLACE INTO `shop_seo_settings` VALUES(\'general\',\'category_products_enable\',\'1\')'
		);
	}
}
catch (waDbException $e)
{

}

try
{
	$result = $model->query('SELECT `value` FROM `shop_seo_settings_product`')
		->fetchAll();
}
catch (waDbException $e)
{
	$result = $model->query(
		'SELECT `product_id`, \'general\' as `storefront`, \'product_name\' as `name`, `name` as `value` FROM `shop_seo_settings_product`'
	)->fetchAll();
	$model->query('DROP TABLE IF EXISTS `shop_seo_settings_product`');
	$model->query(
		'
CREATE TABLE IF NOT EXISTS `shop_seo_settings_product` (
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `storefront` varchar(255) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` text,
  PRIMARY KEY (`product_id`,`storefront`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
	);

	foreach ($result as $r)
	{
		$model->query(
			'INSERT INTO `shop_seo_settings_product` VALUES(\''
			. $model->escape($r['product_id'])
			. '\', \''
			. $model->escape($r['storefront'])
			. '\',\''
			. $model->escape($r['name'])
			. '\',\''
			. $model->escape($r['value'])
			. '\')'
		);
	}
}
