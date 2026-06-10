<?php

$model = new waModel();

try
{
	$model->query('SELECT * FROM `shop_seofilter_filter` LIMIT 0');

	return;
}
catch (Exception $e)
{
	$create_queries = array();

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`seo_name` TEXT NOT NULL,
	`url` VARCHAR(255) NOT NULL,
	`is_enabled` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'1\',
	`feature_value_hash` CHAR(40) NOT NULL,
	`storefronts_use_mode` ENUM(\'ALL\',\'EXCEPT\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	`categories_use_mode` ENUM(\'ALL\',\'EXCEPT\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	`update_datetime` DATETIME NOT NULL,
	`generator_process_id` VARCHAR(16) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `is_enabled` (`is_enabled`),
	INDEX `url_is_enabled` (`url`, `is_enabled`),
	INDEX `is_enabled_feature_value_hash` (`feature_value_hash`, `is_enabled`),
	INDEX `update_datetime` (`update_datetime`),
	INDEX `generator_process_id` (`generator_process_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_category` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`filter_id` INT(11) NOT NULL,
	`category_id` INT(11) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `filter_id_category_id` (`filter_id`, `category_id`),
	INDEX `filter_id` (`filter_id`),
	INDEX `category_id` (`category_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_storefront` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`filter_id` INT(11) NOT NULL,
	`storefront` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `filter_id` (`filter_id`),
	INDEX `storefront` (`storefront`),
	INDEX `filter_id_storefront` (`filter_id`, `storefront`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_feature_value` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`filter_id` INT(11) NOT NULL,
	`feature_id` INT(11) NOT NULL,
	`value_id` INT(11) NULL DEFAULT NULL,
	`sort` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `filter_id` (`filter_id`),
	INDEX `filter_id_feature_id_value_id` (`filter_id`, `feature_id`, `value_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_feature_value_range` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`filter_id` INT(11) NOT NULL,
	`feature_id` INT(11) NOT NULL,
	`type` VARCHAR(16) NOT NULL,
	`begin_base_unit` FLOAT NULL DEFAULT NULL,
	`end_base_unit` FLOAT NULL DEFAULT NULL,
	`unit` VARCHAR(255) NOT NULL,
	`begin` FLOAT NULL DEFAULT NULL,
	`end` FLOAT NULL DEFAULT NULL,
	`sort` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `filter_id` (`filter_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_personal_rule` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`filter_id` INT(11) NOT NULL,
	`is_enabled` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'1\',
	`seo_h1` TEXT NOT NULL,
	`seo_description` TEXT NOT NULL,
	`meta_title` VARCHAR(255) NOT NULL DEFAULT \'\',
	`meta_description` TEXT NOT NULL,
	`meta_keywords` TEXT NOT NULL,
	`storefronts_use_mode` ENUM(\'ALL\',\'EXCEPT\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	`categories_use_mode` ENUM(\'ALL\',\'EXCEPT\',\'LISTED\') NOT NULL DEFAULT \'ALL\',
	PRIMARY KEY (`id`),
	INDEX `filter_id_is_enabled` (`is_enabled`, `filter_id`),
	INDEX `is_enabled` (`is_enabled`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_personal_rule_category` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`rule_id` INT(11) NOT NULL,
	`category_id` INT(11) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `rule_id_category_id` (`rule_id`, `category_id`),
	INDEX `rule_id` (`rule_id`),
	INDEX `category_id` (`category_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_filter_personal_rule_storefront` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`rule_id` INT(11) NOT NULL,
	`storefront` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `rule_id` (`rule_id`),
	INDEX `storefront` (`storefront`),
	INDEX `rule_id_storefront` (`rule_id`, `storefront`)
)
COLLATE=\'utf8_general_ci\';
';





	$create_queries[] = '
CREATE TABLE `shop_seofilter_basic_settings` (
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`name`)
)
COLLATE=\'utf8_general_ci\'
;

';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_storefront_fields` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_storefront_fields_values` (
    `storefront` VARCHAR(255) NOT NULL,
    `field_id` INT(11) NOT NULL,
    `value` TEXT NOT NULL,
    PRIMARY KEY (`storefront`, `field_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_default_template` (
	`storefront` VARCHAR(255) NOT NULL,
	`name` VARCHAR(75) NOT NULL,
	`value` TEXT NOT NULL,
	PRIMARY KEY (`storefront`, `name`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_generator_history` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`generator_id` VARCHAR(16) NOT NULL,
	`total` INT(10) UNSIGNED NULL DEFAULT NULL,
	`created` INT(10) UNSIGNED NULL DEFAULT NULL,
	`skipped` INT(10) UNSIGNED NULL DEFAULT NULL,
	`date` DATETIME NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `generator_id` (`generator_id`),
	INDEX `date` (`date`)
)
COLLATE=\'utf8_general_ci\'
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_generator_history_feature` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`history_id` INT(11) UNSIGNED NOT NULL,
	`feature_id` INT(11) UNSIGNED NOT NULL,
	`order` INT(11) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `history_id` (`history_id`)
)
COLLATE=\'utf8_general_ci\'
;
';

	$create_queries[] = '
CREATE TABLE `shop_seofilter_sitemap_cache` (
	`storefront` VARCHAR(255) NOT NULL,
	`category_id` INT(10) UNSIGNED NOT NULL,
	`filter_ids` TEXT NOT NULL,
	`refresh_after` INT(10) UNSIGNED NOT NULL,
	`lastmod` DATETIME NOT NULL,
	`domain_id` INT(10) UNSIGNED NOT NULL,
	`shop_url` VARCHAR(255) NOT NULL,
	`locked` ENUM(\'Y\',\'N\') NOT NULL DEFAULT \'N\',
	PRIMARY KEY (`storefront`, `category_id`),
	INDEX `refresh_after` (`refresh_after`),
	INDEX `storefront` (`storefront`),
	INDEX `refresh_after_locked` (`refresh_after`, `locked`)
)
COLLATE=\'utf8_general_ci\'
';

	foreach ($create_queries as $query)
	{
		$model->exec($query);
	}

	/**
	 * миграция базы
	 */

	$model = new waModel();


	$filter_ar = new shopSeofilterFilter();

	$count = $model->query('SELECT COUNT(*) FROM shop_seofilter_settings')->fetchField();
	$count = $count == 0
		? $model->query('SELECT COUNT(*) FROM shop_seofilter_settings_fields')->fetchField()
		: 1;
	$count = $count == 0
		? $model->query('SELECT COUNT(*) FROM shop_seofilter_feature_values')->fetchField()
		: 1;

	if ($count == 0 || $filter_ar->countAll() != 0)
	{
		return;
	}

	$storefront_field_model = new shopSeofilterStorefrontFieldsModel();
	$storefront_field_value_model = new shopSeofilterStorefrontFieldsValuesModel();
	/**
	 * перенос названий доп полей витрин
	 */
	$settings_fields_old = $model->query('
SELECT *
FROM shop_seofilter_settings_fields
');
	foreach ($settings_fields_old as $settings_field)
	{
		$storefront_field_model->insert($settings_field);
	}


	/**
	 * Перенос настроек и глобальных мета-тегов
	 */
	$settings_model = new waAppSettingsModel();
	$enabled = $settings_model->get('shop.seofilter', 'enable', '0');

	$settings_old = $model->query('
SELECT *
FROM `shop_seofilter_settings` settings_old
');

	$default_template_model = new shopSeofilterDefaultTemplateModel();
	foreach ($settings_old as $settings_row)
	{
		$storefront = $settings_row['storefront'] == 'general'
			? '*'
			: $settings_row['storefront'];

		if (preg_match('/^storefront_field_(\d+)$/', $settings_row['name'], $matches))
		{
			/**
			 * перенос значений дополнительных полей витрин
			 */
			if (strlen(trim($settings_row['value'])))
			{
				$data = array(
					'storefront' => $storefront,
					'field_id' => $matches[1],
					'value' => $settings_row['value'],
				);

				$storefront_field_value_model->insert($data);
			}
		}
		elseif ($storefront == '*' || $settings_row['value'] != '')
		{
			$data = array(
				'storefront' => $storefront,
				'name' => $settings_row['name'],
				'value' => $settings_row['value'],
			);

			$default_template_model->insert($data);
		}
	}
	unset($settings_row);

	$basic_settings_model = new shopSeofilterBasicSettingsModel();
	$basic_settings_model->insert(array(
		'name' => 'is_enabled',
		'value' => $enabled,
	));


	/**
	 * перенос характеристик/значений
	 */
	$feature_value_settings_old = $model->query('
SELECT DISTINCT t.feature_id, t.value_id
FROM shop_seofilter_feature_values t
');
	foreach ($feature_value_settings_old as $feature_value_old)
	{
		$feature_id = $feature_value_old['feature_id'];
		$value_id = $feature_value_old['value_id'];

		$settings = array(
			'feature_id' => $feature_id,
			'value_id' => $value_id,
		);
		$settings_rows = $model->query('
SELECT *
FROM shop_seofilter_feature_values t
WHERE t.feature_id = :feature_id AND t.value_id = :value_id
', $settings);

		$seo_name = '';
		$url = '';

		/**
		 * перебираем все строки настроек для текущей пары характеристика/значение
		 */
		$filter_personal_tags = array();
		foreach ($settings_rows as $row)
		{
			$field_name = $row['name'];
			$row_value = $row['value'];

			/**
			 * сохраняем seo_name, url
			 */
			if ($field_name == 'seo_name' && strlen($row_value))
			{
				$seo_name = $row_value;

				if (strlen($row['url']))
				{
					$url = $row['url'];
				}

				continue;
			}

			/**
			 * сохраняем мета-теги для всех витрин/категорий
			 */
			$storefront = $row['storefront'];
			$category_id = $row['category_id'];
			$personal_key = $storefront . '|' . $category_id;

			if (!isset($filter_personal_tags[$personal_key]))
			{
				$filter_personal_tags[$personal_key] = array();
			}

			if ($field_name == 'seo_desc')
			{
				$field_name = 'seo_description';
			}
			elseif ($field_name == 'h1')
			{
				$field_name = 'seo_h1';
			}

			$filter_personal_tags[$personal_key][$field_name] = $row_value;
		}
		unset($row);

		if (strlen(trim($url)) == 0 || strlen(trim($seo_name)) == 0)
		{
			//!!!!!
			waLog::dump(array(
				'action' => 'no url',
			), 'seofilter_update.log');
			continue;
		}

		/**
		 * исключаем все пустые наборы тегов
		 */
		$empty_sets = array();
		foreach ($filter_personal_tags as $key => $personal_tags)
		{
			$is_empty = true;
			foreach ($personal_tags as $tag_value)
			{
				if (strlen(trim($tag_value)))
				{
					$is_empty = false;
					break;
				}
			}

			if ($is_empty)
			{
				$empty_sets[] = $key;
				continue;
			}
		}
		foreach ($empty_sets as $key)
		{
			unset($filter_personal_tags[$key]);
		}

		/**
		 * группируем все польностью совпадающие наборы тегов
		 */
		$same_tags_sets = array();
		while (count($filter_personal_tags))
		{
			$keys = array_keys($filter_personal_tags);
			$key = reset($keys);

			$tags = $filter_personal_tags[$key];
			list($storefront, $category_id) = explode('|', $key);

			unset($filter_personal_tags[$key]);

			$set = array(
				'apply' => array(
					$storefront => array($category_id => 1),
				),
				'tags' => $tags,
			);

			$to_unset = array();
			foreach ($filter_personal_tags as $key => $tags_to_compare)
			{
				$are_equal = true;
				$tag_fields = array_unique(array_merge(
					array_keys($tags),
					array_keys($tags_to_compare)
				));

				foreach ($tag_fields as $field)
				{
					if (ifset($tags[$field]) !== ifset($tags_to_compare[$field]))
					{
						$are_equal = false;
						break;
					}
				}

				if (!$are_equal)
				{
					continue;
				}

				$to_unset[] = $key;
				list($new_storefront, $new_category_id) = explode('|', $key);

				if (!isset($set['apply'][$new_storefront]))
				{
					$set['apply'][$new_storefront] = array();
				}
				$set['apply'][$new_storefront][$new_category_id] = 1;
			}
			foreach ($to_unset as $key)
			{
				unset($filter_personal_tags[$key]);
			}

			$same_tags_sets[] = $set;
		}
		unset($filter_personal_tags);


		/**
		 * собираем персональные правила
		 * пытаемся уменьшить количество дублирующих персональных правил
		 */
		$rules = array();
		foreach ($same_tags_sets as $set)
		{
			$rule_attributes = $set['tags'];

			$apply = $set['apply'];

			if (isset($apply['general']))
			{
				$rule = new shopSeofilterFilterPersonalRule_1477563297($rule_attributes);

				$categories = array();
				foreach ($apply['general'] as $category_id => $_)
				{
					if ($category_id == 0)
					{
						$categories = array();
						break;
					}

					$categories[$category_id] = 1;
				}
				if (count($categories))
				{
					$rule->rule_categories = array_keys($categories);
					$rule->categories_use_mode = shopSeofilterFilterPersonalRule_1477563297::USE_MODE_LISTED;
				}

				$rules[] = $rule;
			}
			else
			{
				$rule_sample = new shopSeofilterFilterPersonalRule_1477563297($rule_attributes);
				$rule_sample->storefronts_use_mode = shopSeofilterFilterPersonalRule_1477563297::USE_MODE_LISTED;
				$rule_sample->categories_use_mode = shopSeofilterFilterPersonalRule_1477563297::USE_MODE_LISTED;

				$all_categories_storefronts = array();
				foreach ($apply as $storefront => $categories)
				{
					if (isset($categories['0']))
					{
						$all_categories_rules[$storefront] = 1;
						continue;
					}

					$rule = clone $rule_sample;
					$rule->rule_storefronts = array($storefront);
					$rule->rule_categories = array_keys($categories);

					$rules[] = $rule;
				}

				if (count($all_categories_storefronts))
				{
					$rule = clone $rule_sample;
					$rule->rule_storefronts = array_keys($all_categories_storefronts);
					$rule->categories_use_mode = shopSeofilterFilterPersonalRule_1477563297::USE_MODE_ALL;

					$rules[] = $rule;
				}
			}
		}
		unset($rule);


		/**
		 * характеристика фильтра
		 */
		$filter_feature_value = new shopSeofilterFilterFeatureValue_1477563297();
		$filter_feature_value->feature_id = $feature_id;
		$filter_feature_value->value_id = $value_id;
		$filter_feature_value->sort = 1;


		/**
		 * фильтр
		 */
		$filter = new shopSeofilterFilter_1477563297();
		$filter->url = '_' . $url;
		$filter->seo_name = $seo_name;
		$filter->featureValue = $filter_feature_value;

		if (count($rules))
		{
			$filter->personalRules = $rules;
		}

		try
		{
			$save_success = $filter->save();
			$message = '';
		}
		catch (Exception $exception)
		{
			$save_success = false;
			$message = $exception->getMessage();
		}

		if ($save_success)
		{
			waLog::dump(array(
				'action' => 'filter save error ' . $message,
				'filter' => $filter,
			), 'seofilter_update.log');
		}
	}



	/**
	 * инициализация кеша sitemap'а
	 */
	$LIMIT = 300;

	$storefront_model = new shopSeofilterStorefrontModel();
	$category_model = new shopCategoryModel();

	$routing = wa()->getRouting();
	$domains = $routing->getByApp('shop');
	$storefronts = $storefront_model->getStorefronts();
	$categories = $category_model->getAll('id');

	$prepare_cache_for_routes = array();

	if (count($storefronts) * count($categories) < $LIMIT)
	{
		foreach ($domains as $_domain => $domain_routes)
		{
			foreach ($domain_routes as $_route)
			{
				$_route['storefront'] = $_domain . '/' . $_route['url'];
				$prepare_cache_for_routes[] = $_route;
			}
		}
	}
	else
	{
		$first_storefront = reset($storefronts);
		$product_on_storefront_is_hidden = true;

		foreach ($domains as $_domain => $domain_routes)
		{
			foreach ($domain_routes as $_route)
			{
				if ($_domain . '/' . $_route['url'] == $first_storefront)
				{
					$product_on_storefront_is_hidden = ifset($_route['drop_out_of_stock'], 0) == 2;
					$basic_settings_model->setDefaultSitemapCacheStorefront($first_storefront, $product_on_storefront_is_hidden);

					$_route['domain'] = $_domain;
					$_route['storefront'] = $_domain . '/' . $_route['url'];
					$prepare_cache_for_routes[] = $_route;

					break 2;
				}
			}
		}

		$second_storefront = null;

		foreach ($domains as $_domain => $domain_routes)
		{
			foreach ($domain_routes as $_route)
			{
				$storefront = $_domain . '/' . $_route['url'];
				$_route['domain'] = $_domain;
				$_route['storefront'] = $storefront;

				if ($second_storefront === null && $product_on_storefront_is_hidden !== (ifset($_route['drop_out_of_stock'], 0) == 2))
				{
					$second_storefront = $storefront;
					$prepare_cache_for_routes[] = $_route;
					$basic_settings_model->setDefaultSitemapCacheStorefront($second_storefront, !$product_on_storefront_is_hidden);

					break 2;
				}
			}
		}
	}


	wa('site');
	$domain_model = new siteDomainModel();
	$domain_rows = $domain_model->getAll('name');
	$sitemap_cache_model = new shopSeofilterSitemapCacheModel();

	/**
	 * заполняем кеш
	 */
	$storage = new shopSeofilterFiltersStorage();
	$refresh_after = time() + shopSeofilterSitemapCache::CACHE_TTL;

	foreach ($prepare_cache_for_routes as $route)
	{
		$storefront = $route['storefront'];
		foreach ($categories as $category_id => $category)
		{
			$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);
			$collection->filters(array(
				'in_stock_only' => ifset($route['drop_out_of_stock'], 0) == 2,
			));

			$feature_value_ids = $collection->getFeatureValueIds();

			$filters = $storage->getAllForCategory($storefront, $category_id);
			$filter_ids = array();
			foreach ($filters as $filter)
			{
				$feature_values = $filter->featureValues;
				$feature_value = reset($feature_values);
				if ($feature_value === false)
				{
					continue;
				}

				$has_products = isset($feature_value_ids[$feature_value->feature_id]) && in_array($feature_value->feature_id, $feature_value_ids[$feature_value->feature_id], false);
				if ($has_products)
				{
					$filter_ids[] = $filter->id;
				}
			}

			$to_insert = array(
				'storefront' => $storefront,
				'category_id' => $category_id,
				'filter_ids' => $filter_ids,
				'refresh_after' => $refresh_after,
				'lastmod' => ifset($category['edit_datetime'], $category['create_datetime']),
				'domain_id' => ifset($domain_rows[$route['domain']]['id'], 0),
				'shop_url' => $route['url'],
			);

			$refresh_after += shopSeofilterSitemapCache::CACHE_UPDATE_MINIMUM_INTERVAL;
			$sitemap_cache_model->save($to_insert);
		}
	}








	$old_tables = array(
		'shop_seofilter_settings',
		'shop_seofilter_settings_fields',
		'shop_seofilter_feature_values',
	);

	foreach ($old_tables as $old_table)
	{
		$model->exec('
ALTER TABLE `' . $old_table . '`
	COMMENT=\'старая таблица seo-фильтра - не удаляем на случай, если придется откатываться обратно до 1.6\r\nбудет удалена в версии 2.2\';
');
	}

	/**
	 * не удаляем старые таблицы на случай, если придется откатываться обратно до 1.6
	 */
	//foreach ($old_tables as $old_table)
	//{
	//	$model->exec('DROP TABLE IF EXISTS ' . $old_table);
	//}
}