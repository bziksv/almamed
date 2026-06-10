<?php

class shopSeofilterMigrateCli extends waCliController
{
	public function execute()
	{
		echo "replaceVariables...";
		try
		{
			$this->replaceVariables();
			echo " done!" . PHP_EOL;
		}
		catch (Exception $e)
		{
			echo " ERROR: {$e->getMessage()}" . PHP_EOL;
		}
	}

	private function migrateGlobalMeta()
	{
		$model = new waModel();
		$storefront_field_value_model = new shopSeofilterStorefrontFieldsValuesModel();

		/**
		 * Перенос настроек и глобальных мета-тегов
		 */
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
	}

	private function migrateFiltersWithPersonalMeta()
	{
		$model = new waModel();

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
	}

	private function replaceVariables()
	{
		$default_template_model = new shopSeofilterDefaultTemplateModel();
		$personal_rule_model = new shopSeofilterFilterPersonalRuleModel();

		$replaces = array(
			'from' => array(
				'{category_seo_name',
				'{seo_name',
				'{region_name',
				'{page_number',
				'{store_name',
				'{category_name',
				'{region_phone',
			),
			'to' => array(
				'{$category.seo_name',
				'{$seo_name',
				'{$region.name',
				'{$page_number',
				'{$store_info.name',
				'{$category.name',
				'{$region.phone',
			),
		);

		foreach ($default_template_model->select('*')->query() as $row)
		{
			$meta_value = $row['value'];

			$default_template_model->updateByField(array(
				'storefront' => $row['storefront'],
				'name' => $row['name'],
			), array(
				'value' => str_replace($replaces['from'], $replaces['to'], $meta_value)
			));
		}

		foreach ($personal_rule_model->select('*')->query() as $row)
		{
			$update = array(
				'seo_h1' => str_replace($replaces['from'], $replaces['to'], $row['seo_h1']),
				'seo_description' => str_replace($replaces['from'], $replaces['to'], $row['seo_description']),
				'meta_title' => str_replace($replaces['from'], $replaces['to'], $row['meta_title']),
				'meta_description' => str_replace($replaces['from'], $replaces['to'], $row['meta_description']),
				'meta_keywords' => str_replace($replaces['from'], $replaces['to'], $row['meta_keywords']),
			);

			$personal_rule_model->updateByField('id', $row['id'], $update);
		}
	}
}