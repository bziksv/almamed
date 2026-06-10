<?php

$model = new waModel();

try
{
	$model->query('select * from `shop_seo_template` limit 0');
}
catch (Exception $e)
{
	/*  Перенос обычных настроек
		--- */
	$model->query('drop table if exists `shop_seo_settings_new`');
	$model->query('CREATE TABLE if not exists `shop_seo_settings_new` (
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `group_id` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`storefront_id`,`group_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
	$model->query('drop table if exists `shop_seo_template`');
	$model->query('CREATE TABLE if not exists `shop_seo_template` (
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `group_id` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`storefront_id`,`group_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('drop table if exists `shop_seo_settings_plugin`');
	$model->query('CREATE TABLE if not exists `shop_seo_settings_plugin` (
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('CREATE TABLE if not exists `shop_seo_field_storefront_value` (
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `field_id` int(11) NOT NULL DEFAULT \'0\',
  `value` text,
  PRIMARY KEY (`storefront_id`,`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('delete from `shop_seo_settings` where `value`=\'\'');
	$result = $model->query('select * from `shop_seo_settings`');
	$new_settings = array();
	$new_templates = array();
	$fields_values = array();
	$add_category_h1_replace = false;
	$add_product_h1_replace = false;

	while ($setting = $result->fetchAssoc())
	{
		$tmp = explode('_', $setting['name'], 2);
		$group = null;

		switch ($tmp[0])
		{
			case 'main':
				$group = 'home';
				break;
			case 'categories':
				$group = 'category';
				break;
			case 'products':
				$group = 'product';
				break;
			case 'static':
				$group = 'page';
				break;
			case 'tags':
				$group = 'tag';
				break;
			case 'brands':
				$group = 'brand';
				break;
			default:
				$tmp[1] = $setting['name'];
				break;
		}

		$is_setting = true;
		$name = null;

		switch ($tmp[1])
		{
			case 'enable':
				$name = 'is_enable';
				break;
			case 'meta_overwrite':
				$name = 'ignore_meta_data';
				break;
			case 'storefront_name':
				$group = 'data';
				$name = 'storefront_name';
				break;
			case 'plugin_enable':
				$group = 'plugin';
				$name = 'is_enable';
				break;
			case 'category_subcategories_enable':
				$group = 'plugin';
				$name = 'category_subcategory_is_enable';
				break;
			case 'category_products_enable':
				$group = 'plugin';
				$name = 'category_product_is_enable';
				break;
			case 'replace_header_enable':
				if ($group == 'category')
				{
					$model->query('replace into `shop_seo_template`(`storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?)',
						'*', 'category', 'h1', '{$category.seo_name}');
				}
				else
				{
					$model->query('replace into `shop_seo_template`(`storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?)',
						'*', 'product', 'h1', '{$product.seo_name}');
				}
				break;
			case 'category_additional_description_enable':
				$group = 'plugin';
				$name = 'category_additional_description_is_enable';
				break;
			default:
				if (preg_match('/^storefront_field_(\d+)$/', $tmp[1], $matches))
				{
					$id = $matches[1];
					$model->query('replace into `shop_seo_field_storefront_value`(`storefront_id`, `field_id`, `value`) values (?, ?, ?)',
						$setting['storefront'] == 'general' ? '*' : $setting['storefront'], $id, $setting['value']);
				}
				else
				{
					$name = $tmp[1];
					$is_setting = false;
				}
				break;
		}

		if ($group && $name)
		{
			if ($group == 'plugin')
			{
				$model->query('replace into `shop_seo_settings_plugin`(`name`, `value`) values (?, ?)',
					$name, $setting['value']);
			}
			elseif ($is_setting)
			{
				$model->query('replace into `shop_seo_settings_new`(`storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?)',
					$setting['storefront'] == 'general' ? '*' : $setting['storefront'], $group, $name,
					$setting['value']);
			}
			else
			{
				$model->query('replace into `shop_seo_template`(`storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?)',
					$setting['storefront'] == 'general' ? '*' : $setting['storefront'], $group, $name,
					$setting['value']);
			}
		}
	}

	$plugin_settings = $new_settings['plugin'];
	unset($new_settings['plugin']);

	$model->query('drop table if exists `shop_seo_settings`');
	$model->query('ALTER TABLE `shop_seo_settings_new` RENAME `shop_seo_settings`');


	/*  Перенос настроек категорий
		--- */

	$model->query('drop table if exists `shop_seo_settings_category_new`');
	$model->query('CREATE TABLE if not exists `shop_seo_settings_category_new` (
  `category_id` int(11) NOT NULL DEFAULT \'0\',
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `group_id` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`category_id`,`storefront_id`,`group_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('drop table if exists `shop_seo_template_category`');
	$model->query('CREATE TABLE if not exists `shop_seo_template_category` (
  `category_id` int(11) NOT NULL DEFAULT \'0\',
  `storefront_id` varchar(255) NOT NULL,
  `group_id` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`category_id`,`storefront_id`,`group_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('delete from `shop_seo_settings_category` where `value`=\'\'');
	$result = $model->query('select * from `shop_seo_settings_category`');
	$new_settings = array();
	$new_templates = array();

	while ($setting = $result->fetchAssoc())
	{
		$tmp = explode('_', $setting['name'], 2);
		$group = null;

		switch ($tmp[0])
		{
			case 'category':
				$group = 'data';
				break;
			case 'subcategories':
				$group = 'subcategory';
				break;
			case 'products':
				$group = 'product';
				break;
			default:
				$tmp[1] = $setting['name'];
				break;
		}

		$is_setting = true;
		$name = null;

		switch ($tmp[1])
		{
			case 'enable':
				$name = 'is_enable';
				break;
			case 'meta_overwrite':
				$name = 'ignore_meta_data';
				break;
			case 'name':
				$name = 'seo_name';
				$is_setting = false;
				break;
			default:
				$name = $tmp[1];
				$is_setting = false;
				break;
		}

		if ($group && $name)
		{
			if ($is_setting)
			{
				$model->query('replace into `shop_seo_settings_category_new`(`category_id`, `storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?, ?)',
					$setting['category_id'], $setting['storefront'] == 'general' ? '*' : $setting['storefront'], $group,
					$name, $setting['value']);
			}
			else
			{
				$model->query('replace into `shop_seo_template_category`(`category_id`, `storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?, ?)',
					$setting['category_id'], $setting['storefront'] == 'general' ? '*' : $setting['storefront'], $group,
					$name, $setting['value']);
			}
		}
	}

	$model->query('drop table if exists `shop_seo_settings_category`');
	$model->query('ALTER TABLE `shop_seo_settings_category_new` RENAME `shop_seo_settings_category`');


	/*  Перенос настроек товаров
		--- */

	$model->query('drop table if exists `shop_seo_settings_product_new`');
	$model->query('CREATE TABLE if not exists `shop_seo_settings_product_new` (
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `group_id` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`product_id`,`storefront_id`,`group_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('drop table if exists `shop_seo_template_product`');
	$model->query('CREATE TABLE if not exists `shop_seo_template_product` (
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `storefront_id` varchar(255) NOT NULL,
  `group_id` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `value` text,
  PRIMARY KEY (`product_id`,`storefront_id`,`group_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('delete from `shop_seo_settings_product` where `value`=\'\'');
	$result = $model->query('select * from `shop_seo_settings_product`');
	$new_settings = array();
	$new_templates = array();

	while ($setting = $result->fetchAssoc())
	{
		$tmp = explode('_', $setting['name'], 2);
		$group = null;

		switch ($tmp[0])
		{
			case 'product':
				$group = 'data';
				break;
			default:
				$tmp[1] = $setting['name'];
				break;
		}

		$is_setting = true;
		$name = null;

		switch ($tmp[1])
		{
			case 'name':
				$name = 'seo_name';
				$is_setting = false;
				break;
			default:
				$name = $tmp[1];
				$is_setting = false;
				break;
		}

		if ($group && $name)
		{
			if ($is_setting)
			{
				$model->query('replace into `shop_seo_settings_product_new`(`product_id`, `storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?, ?)',
					$setting['product_id'], $setting['storefront'] == 'general' ? '*' : $setting['storefront'], $group,
					$name, $setting['value']);
			}
			else
			{
				$model->query('replace into `shop_seo_template_product`(`product_id`, `storefront_id`, `group_id`, `name`, `value`) values (?, ?, ?, ?, ?)',
					$setting['product_id'], $setting['storefront'] == 'general' ? '*' : $setting['storefront'], $group,
					$name, $setting['value']);
			}
		}
	}

	$model->query('drop table if exists `shop_seo_settings_product`');
	$model->query('ALTER TABLE `shop_seo_settings_product_new` RENAME `shop_seo_settings_product`');


	/*  Перенос полей
		--- */

	$model->query('drop table if exists `shop_seo_field_storefront`');
	$model->query('ALTER TABLE `shop_seo_settings_field` RENAME `shop_seo_field_storefront`');

	$model->query('CREATE TABLE if not exists `shop_seo_field_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('CREATE TABLE if not exists `shop_seo_field_category_value` (
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `category_id` int(11) NOT NULL DEFAULT \'0\',
  `field_id` int(11) NOT NULL DEFAULT \'0\',
  `value` text,
  PRIMARY KEY (`storefront_id`,`category_id`,`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('CREATE TABLE if not exists `shop_seo_field_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
	$model->query('CREATE TABLE if not exists `shop_seo_field_product_value` (
  `storefront_id` varchar(255) NOT NULL DEFAULT \'\',
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `field_id` int(11) NOT NULL DEFAULT \'0\',
  `value` text,
  PRIMARY KEY (`storefront_id`,`product_id`,`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');
}