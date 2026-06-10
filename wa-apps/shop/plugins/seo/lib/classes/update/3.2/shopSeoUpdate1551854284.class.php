<?php

class shopSeoUpdate1551854284
{
	private static $settings_name_map
		= array(
			'is_enable' => 'is_enabled',
		);

	private static $settings_group_map
		= array(
			'home' => 'home_page',
		);

	private static $settings_map
		= array(
			'data_seo_name' => 'seo_name',
			'data_storefront_name' => 'storefront_name',
			'data_meta_title' => 'meta_title',
			'data_h1' => 'h1',
			'data_meta_keywords' => 'meta_keywords',
			'data_meta_description' => 'meta_description',
			'data_description' => 'description',
			'data_additional_description' => 'additional_description',
		);

	public function update()
	{
		$this->cleanFiles();
		$this->createTables();
		$this->moveTable('shop_seo_field_storefront', 'shop_seo_storefront_field');
		$this->moveTable('shop_seo_field_category', 'shop_seo_category_field');
		$this->moveTable('shop_seo_field_product', 'shop_seo_product_field');
		$this->transferPluginSettings();
		$storefronts = $this->getStorefronts();
		$is_success = $this->createStorefrontsGroups($storefronts, $storefronts_map);

		if (!$is_success)
		{
			return;
		}

		$storefronts_map['*'] = 0;
		$this->transferStorefrontFieldsValues($storefronts_map);
		$this->transferCategoryFieldsValues($storefronts_map);
		$this->transferProductFieldsValues($storefronts_map);
		$this->transferStorefrontSettings($storefronts_map);
		$this->transferCategorySettings($storefronts_map);
		$this->transferProductSettings($storefronts_map);

	}

	private function cleanFiles()
	{
		$cleaner = new shopSeoCleaner();
		$cleaner->clean();
	}

	private function createTables()
	{
		$path = wa('shop')->getAppPath('plugins/seo/lib/config/db.php');
		$schema = include($path);
		$model = new waModel();

		try
		{
			$model->createSchema($schema);
		}
		catch (Exception $ignored)
		{
		}
	}

	private function transferPluginSettings()
	{
		try
		{
			$model = new waModel();
			$map = array(
				'is_enable' => 'is_enabled',
				'category_subcategory_is_enable' => 'category_subcategories_is_enabled',
				'category_product_is_enable' => 'category_products_is_enabled',
				'category_pagination_is_enable' => 'category_pagination_is_enabled',
				'category_product_h1_is_enable' => 'category_product_h1_is_enabled',
				'product_review_is_enable' => 'product_review_is_enabled',
				'product_page_is_enable' => 'product_page_is_enabled',
				'category_additional_description_is_enable' => 'category_additional_description_is_enabled',
				'product_additional_description_is_enable' => 'product_additional_description_is_enabled',
				'append_page_number_is_enable' => 'page_number_is_enabled',
				'cache_is_enable' => 'cache_is_enabled',
				'cache_ttl_in_min' => 'cache_variant',
			);

			$rows = $model->query('select * from shop_seo_settings_plugin')->fetchAll();
			$result_rows = array();

			foreach ($rows as $row)
			{
				if (isset($map[$row['name']]))
				{
					$row['name'] = $map[$row['name']];
					$result_rows[] = $row;
				}
			}

			$plugin_settings_model = new shopSeoWaTableModel('shop_seo_plugin_settings');

			foreach ($result_rows as $row)
			{
				$plugin_settings_model->replace($row);
			}

			$model->exec('drop table shop_seo_settings_plugin');
		}
		catch (Exception $ignored)
		{

		}
	}

	private function mapGroupAndName($group, $name)
	{
		if (isset(self::$settings_group_map[$group]))
		{
			$group = self::$settings_group_map[$group];
		}

		if (isset(self::$settings_name_map[$name]))
		{
			$name = self::$settings_name_map[$name];
		}

		$name = "{$group}_{$name}";

		if (isset(self::$settings_map[$name]))
		{
			$name = self::$settings_map[$name];
		}

		return $name;
	}

	private function moveTable($from_table, $to_table)
	{
		try
		{
			$model = new waModel();
			$model->exec("insert ignore into `{$to_table}` select * from `{$from_table}`");
			$model->exec("drop table `{$from_table}`");
		}
		catch (Exception $ignored)
		{
		}
	}

	private function getStorefronts()
	{
		$storefronts = array();
		$model = new waModel();

		foreach (array(
			'shop_seo_field_storefront_value',
			'shop_seo_field_category_value',
			'shop_seo_field_product_value',
			'shop_seo_settings',
			'shop_seo_template',
			'shop_seo_settings_category',
			'shop_seo_template_category',
			'shop_seo_settings_product',
			'shop_seo_template_product',
		) as $table)
		{
			try
			{
				$model->exec("delete from `{$table}` where value = ''");
				$rows = $model->query("select distinct storefront_id from `{$table}`")->fetchAll();

				foreach ($rows as $row)
				{
					$storefronts[] = $row['storefront_id'];
				}
			}
			catch (Exception $ignored)
			{

			}
		}

		$storefronts = array_unique($storefronts);
		$storefronts = array_diff($storefronts, array('*'));
		$all_storefronts = $this->getAllStorefronts();
		$storefronts = array_intersect($storefronts, $all_storefronts);

		return $storefronts;
	}

	private function getAllStorefronts()
	{
		$routing = wa()->getRouting();
		$domains = $routing->getByApp('shop');
		$urls = array();

		foreach ($domains as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if ((!method_exists($routing, 'isAlias') || !$routing->isAlias($domain)) and isset($route['url']))
				{
					$urls[] = $domain . '/' . $route['url'];
				}
			}
		}

		return $urls;
	}

	private function createStorefrontsGroups($storefronts, &$storefronts_map)
	{
		$storefronts_map = array_fill_keys($storefronts, null);

		$model = new waModel();

		try
		{
			$row = $model->query('select max(sort) max from shop_seo_group_storefront')->fetch();

			if (is_null($row['max']))
			{
				$sort = 0;
			}
			else
			{
				$sort = $row['max'] + 1;
			}

			$group_storefront_model = new shopSeoWaTableModel('shop_seo_group_storefront');
			$group_storefront_storefront_model = new shopSeoWaTableModel('shop_seo_group_storefront_storefront');

			foreach ($storefronts as $storefront)
			{
				$row = array(
					'id' => null,
					'name' => $storefront,
					'storefront_select_rule_type' => 'INCLUDE',
					'sort' => $sort++,
				);

				$id = $group_storefront_model->insert($row);
				$storefronts_map[$storefront] = $id;

				$row = array(
					'group_id' => $id,
					'storefront' => $storefront,
				);
				$group_storefront_storefront_model->insert($row);
			}
		}
		catch (Exception $ignored)
		{
			return false;
		}

		return true;
	}

	private function transferStorefrontFieldsValues($storefronts_map)
	{
		$model = new waModel();

		try
		{
			$storefront_field_value_model = new shopSeoWaTableModel('shop_seo_storefront_field_value');

			foreach ($model->query('select * from shop_seo_field_storefront_value') as $row)
			{
				$new_row = array(
					'group_id' => $storefronts_map[$row['storefront_id']],
					'field_id' => $row['field_id'],
					'value' => $row['value'],
				);

				$storefront_field_value_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_field_storefront_value');
		}
		catch (Exception $ignored)
		{

		}
	}

	private function transferCategoryFieldsValues($storefronts_map)
	{
		$model = new waModel();

		try
		{
			$category_field_value_model = new shopSeoWaTableModel('shop_seo_category_field_value');

			foreach ($model->query('select * from shop_seo_field_category_value') as $row)
			{
				$new_row = array(
					'group_storefront_id' => $storefronts_map[$row['storefront_id']],
					'category_id' => $row['category_id'],
					'field_id' => $row['field_id'],
					'value' => $row['value'],
				);

				$category_field_value_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_field_category_value');
		}
		catch (Exception $ignored)
		{

		}
	}

	private function transferProductFieldsValues($storefronts_map)
	{
		$model = new waModel();

		try
		{
			$product_field_value_model = new shopSeoWaTableModel('shop_seo_product_field_value');

			foreach ($model->query('select * from shop_seo_field_product_value') as $row)
			{
				$new_row = array(
					'group_storefront_id' => $storefronts_map[$row['storefront_id']],
					'product_id' => $row['product_id'],
					'field_id' => $row['field_id'],
					'value' => $row['value'],
				);

				$product_field_value_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_field_product_value');
		}
		catch (Exception $ignored)
		{

		}
	}

	private function transferStorefrontSettings($storefronts_map)
	{
		$model = new waModel();

		try
		{
			$storefront_settings_model = new shopSeoWaTableModel('shop_seo_storefront_settings');

			foreach ($model->query('select * from shop_seo_settings') as $row)
			{
				$name = $this->mapGroupAndName($row['group_id'], $row['name']);

				$new_row = array(
					'group_id' => $storefronts_map[$row['storefront_id']],
					'name' => $name,
					'value' => $row['value'],
				);

				$storefront_settings_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_settings');
		}
		catch (Exception $ignored)
		{

		}

		try
		{
			$storefront_settings_model = new shopSeoWaTableModel('shop_seo_storefront_settings');

			foreach ($model->query('select * from shop_seo_template') as $row)
			{
				$name = $this->mapGroupAndName($row['group_id'], $row['name']);

				$new_row = array(
					'group_id' => $storefronts_map[$row['storefront_id']],
					'name' => $name,
					'value' => $row['value'],
				);

				$storefront_settings_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_template');
		}
		catch (Exception $ignored)
		{

		}
	}

	private function transferCategorySettings($storefronts_map)
	{
		$model = new waModel();

		try
		{
			$category_settings_model = new shopSeoWaTableModel('shop_seo_category_settings');

			foreach ($model->query('select * from shop_seo_settings_category') as $row)
			{
				$name = $this->mapGroupAndName($row['group_id'], $row['name']);

				$new_row = array(
					'group_storefront_id' => $storefronts_map[$row['storefront_id']],
					'category_id' => $row['category_id'],
					'name' => $name,
					'value' => $row['value'],
				);

				$category_settings_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_settings_category');
		}
		catch (Exception $ignored)
		{

		}

		try
		{
			$category_settings_model = new shopSeoWaTableModel('shop_seo_category_settings');

			foreach ($model->query('select * from shop_seo_template_category') as $row)
			{
				$name = $this->mapGroupAndName($row['group_id'], $row['name']);

				$new_row = array(
					'group_storefront_id' => $storefronts_map[$row['storefront_id']],
					'category_id' => $row['category_id'],
					'name' => $name,
					'value' => $row['value'],
				);

				$category_settings_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_template_category');
		}
		catch (Exception $ignored)
		{

		}
	}

	private function transferProductSettings($storefronts_map)
	{
		$model = new waModel();

		try
		{
			$product_settings_model = new shopSeoWaTableModel('shop_seo_product_settings');

			foreach ($model->query('select * from shop_seo_settings_product') as $row)
			{
				$name = $this->mapGroupAndName($row['group_id'], $row['name']);

				$new_row = array(
					'group_storefront_id' => $storefronts_map[$row['storefront_id']],
					'product_id' => $row['product_id'],
					'name' => $name,
					'value' => $row['value'],
				);

				$product_settings_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_settings_product');
		}
		catch (Exception $ignored)
		{

		}

		try
		{
			$product_settings_model = new shopSeoWaTableModel('shop_seo_product_settings');

			foreach ($model->query('select * from shop_seo_template_product') as $row)
			{
				$name = $this->mapGroupAndName($row['group_id'], $row['name']);

				$new_row = array(
					'group_storefront_id' => $storefronts_map[$row['storefront_id']],
					'product_id' => $row['product_id'],
					'name' => $name,
					'value' => $row['value'],
				);

				$product_settings_model->replace($new_row);
			}

			$model->exec('drop table shop_seo_template_product');
		}
		catch (Exception $ignored)
		{

		}
	}
}