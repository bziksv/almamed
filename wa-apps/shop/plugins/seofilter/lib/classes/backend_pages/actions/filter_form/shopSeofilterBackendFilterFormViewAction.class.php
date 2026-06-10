<?php

abstract class shopSeofilterBackendFilterFormViewAction extends shopSeofilterBackendViewAction
{
	private $all_storefronts = array();

	protected function preExecute()
	{
		parent::preExecute();

		$this->all_storefronts = array();
		foreach (wa()->getRouting()->getByApp('shop') as $domain => $route_attributes_arr)
		{
			foreach ($route_attributes_arr as $route_attributes)
			{
				$storefront = $domain . '/' . $route_attributes['url'];
				$this->all_storefronts[] = array(
					'value' => $storefront,
					'label' => trim($storefront, '*/'),
				);
			}
		}
	}

	protected function prepareForm(shopSeofilterFilter $filter)
	{
		$this->addCss(array(
			'shop.css',
			'react-select.css',
			'react-popup.css',
			'bs_ui.css',
			'form.css',
		));

		if ($filter->id > 0)
		{
			$this->left_sidebar['pages']['clone'] = array(
				'text' => 'Клонировать фильтр',
				'href' => '?plugin=seofilter&action=clone&id=' . $filter->id,
				'current' => waRequest::param('action') == 'clone',
				'icon_class' => 'folders',
			);
		};

		$settings = shopSeofilterBasicSettingsModel::getSettings();


		$this->view->assign('storefront_fields', shopSeofilterStorefrontFieldsModel::getAllFields());
		$this->view->assign('filter', $this->prepareFilterAttributes($filter));
		$this->view->assign('additional_description_is_enabled', $settings->category_additional_description_is_enabled);

		$storefront_categories = array();

		$this->view->assign('storefront_categories', $storefront_categories);

		$this->assignAllCategoriesAndStorefronts();
		$this->assignFilterFeatureValues($filter);
		$this->assignFilterPersonalRules($filter);
		$this->assignFeaturesWithValues();
		$this->assignUnits();
		$this->assignCustomTemplateMeta();
		$this->assignFilterFieldValues($filter);
		$this->assignFilterPersonalCanonicals($filter);
	}

	private function prepareFilterAttributes(shopSeofilterFilter $filter)
	{
		$filter_attributes = $filter->getAttributes();

		$filter_attributes['storefronts'] = array(
			'values' => $filter->filter_storefronts,
			'use_mode' => $filter->storefronts_use_mode,
		);
		$filter_attributes['categories'] = array(
			'values' => $filter->filter_categories,
			'use_mode' => $filter->categories_use_mode,
		);

		$filter_attributes['is_enabled'] = $filter_attributes['is_enabled'] == shopSeofilterFilter::ENABLED;

		return $filter_attributes;
	}

	private function assignAllCategoriesAndStorefronts()
	{
		$category_model = new shopCategoryModel();
		$full_tree = $category_model->getFullTree();

		$list_ordered = array();
		$category_names = array();
		foreach ($full_tree as $category)
		{
			$list_ordered[] = array(
				'value' => $category['id'],
				'label' => str_repeat('-', $category['depth'] + 1) . $category['name'],
				'category_is_hidden' => $category['status'] == '0',
			);
			$category_names[$category['id']] = $category['name'];
		}

		$this->view->assign('all_categories', $list_ordered);
		$this->view->assign('category_names', $category_names);
		$this->view->assign('all_storefronts', $this->all_storefronts);
	}

	private function assignFilterFeatureValues(shopSeofilterFilter $filter)
	{
		$feature_values = array();

		foreach ($filter->featureValues as $feature_value)
		{
			$feature_value_data = array_merge(
				$feature_value->getAttributes(),
				array(
					'feature_label' => "-- характеристика удалена (id {$feature_value->feature_id}) --",
					'feature_code' => '',
					'value_label' => "-- значение характеристики удалено (id {$feature_value->value_id}) --",
					'is_range' => false,
					'type' => null,
					'feature_type' => null,
					'unit' => null,
					'_sort' => $feature_value->sort,
				)
			);

			if ($feature_value->feature)
			{
				$feature_value_data['feature_label'] = $feature_value->feature->name;
				$feature_value_data['feature_code'] = $feature_value->feature->code;
				$feature_value_data['feature_type'] = $feature_value->feature->type;
			}

			if ($feature_value->featureValue)
			{
				$feature_value_data['value_label'] = $feature_value->featureValue['value'];
				$feature_value_data['type'] = isset($feature_value->featureValue['type']) ? $feature_value->featureValue['type'] : null;
				$feature_value_data['unit'] = isset($feature_value->featureValue['unit']) ? $feature_value->featureValue['unit'] : null;
			}

			unset($feature_value_data['sort']);

			$feature_values[] = $feature_value_data;
		}

		foreach ($filter->featureValueRanges as $feature_value_range)
		{
			if (!$feature_value_range->feature && !$feature_value_range->isPrice())
			{
				continue;
			}

			$data = $feature_value_range->getAttributes();
			$data['is_range'] = true;
			$data['_sort'] = $feature_value_range->sort;

			if ($data['begin'] === null)
			{
				$data['begin'] = '';
			}

			if ($data['end'] === null)
			{
				$data['end'] = '';
			}

			$currency_model = new shopCurrencyModel();
			$currencies = $currency_model->getCurrencies();

			unset($data['sort']);

			if ($feature_value_range->isPrice())
			{
				$data['feature_id'] = shopSeofilterFilter::TYPE_PRICE . '_' . $feature_value_range->unit;
				$data['feature_label'] = isset($currencies[$feature_value_range->unit])
					? 'Цена в ' . $currencies[$feature_value_range->unit]['sign']
					: 'Цена';
				$data['feature_type'] = 'price';
			}
			else
			{
				$data['feature_code'] = $feature_value_range->feature->code;
				$data['feature_label'] = $feature_value_range->feature->name;
				$data['feature_type'] = $feature_value_range->feature->type;
			}

			$feature_values[] = $data;
		}

		$this->view->assign(array(
			'filter_feature_values' => $feature_values,
		));
	}

	private function assignFilterPersonalRules(shopSeofilterFilter $filter)
	{
		$rules_list = array();

		foreach ($filter->personalRules as $rule)
		{
			$rule_attributes = array_merge(
				$rule->getAttributes(),
				array(
					'storefronts' => $rule->rule_storefronts,
					'categories' => $rule->rule_categories,
				)
			);
			$rule_attributes['is_enabled'] = $rule->is_enabled == shopSeofilterFilterPersonalRule::ENABLED;
			$rule_attributes['is_pagination_templates_enabled'] = $rule->is_pagination_templates_enabled == shopSeofilterFilterPersonalRule::ENABLED;

			$rule_attributes['storefronts'] = array(
				'values' => $rule_attributes['storefronts'],
				'use_mode' => $rule->storefronts_use_mode,
			);
			$rule_attributes['categories'] = array(
				'values' => $rule_attributes['categories'],
				'use_mode' => $rule->categories_use_mode,
			);

			$rules_list[] = $rule_attributes;
		}

		$this->assignPersonalRuleUseModeOptions();
		$this->view->assign('filter_personal_rules', $rules_list);
	}

	private function assignPersonalRuleUseModeOptions()
	{
		$use_mode_options = array(
			array(
				'value' => shopSeofilterFilterPersonalRule::USE_MODE_ALL,
				'title' => 'Все',
			),
			array(
				'value' => shopSeofilterFilterPersonalRule::USE_MODE_LISTED,
				'title' => 'Только для',
			),
			array(
				'value' => shopSeofilterFilterPersonalRule::USE_MODE_EXCEPT,
				'title' => 'Для всех, кроме',
			),
		);
		$this->view->assign('use_mode_options', $use_mode_options);
	}

	private function assignFeaturesWithValues()
	{
		$features_with_values = array();

		$currency_model = new shopCurrencyModel();
		$currencies = $currency_model->getCurrencies();
		if (count($currencies) > 0)
		{
			foreach ($currencies as $code => $currency)
			{
				$features_with_values[shopSeofilterFilter::TYPE_PRICE . '_' . $code] = array(
					'id' => shopSeofilterFilter::TYPE_PRICE . '_' . $code,
					'type' => shopSeofilterFilter::TYPE_PRICE,
					'name' => 'Цена в ' . $currency['sign'],
					'is_range' => true,
				);
			}
		}

		$feature_model = new shopFeatureModel();

		$features = $feature_model
			->select('*')
			->where("(`selectable`=1 OR `type`='boolean' OR `type`='double' OR `type` LIKE 'dimension.%' OR `type` LIKE 'range.%')")
			->where('`parent_id` IS NULL')
			->where("`type` NOT LIKE '2d.%'")
			->where("`type` NOT LIKE '3d.%'")
			->query();


		foreach ($features as $feature)
		{
			$feature_id = $feature['id'];
			$type = $feature['type'];

			try
			{
				$values_model = shopFeatureModel::getValuesModel($type);
				if (!$values_model)
				{
					continue;
				}
			}
			catch (waException $e)
			{
				continue;
			}

			$features_with_values[$feature_id] = $feature;
			$features_with_values[$feature_id]['is_range'] = false;

			if ($type === shopFeatureModel::TYPE_BOOLEAN)
			{
				$features_with_values[$feature_id]['values'] = array(
					array('value' => '1', 'label' => 'Да',),
					array('value' => '0', 'label' => 'Нет'),
				);

				continue;
			}

			$values = $values_model
				->select('*')
				->where('feature_id = :feature_id', array('feature_id' => $feature_id))
				->query();

			if (strpos($type, shopFeatureModel::TYPE_DIMENSION) === 0)
			{
				$features_with_values[$feature_id]['values'] = array();
				foreach ($values as $row)
				{
					$value = new shopDimensionValue($row);
					$features_with_values[$feature_id]['values'][] = array(
						'value' => '' . $value->id,
						'label' => $value->value . ' ' . $value->unit_name,
					);
				}
			}
			elseif ($type === shopFeatureModel::TYPE_VARCHAR || $type === shopFeatureModel::TYPE_DOUBLE || $type === shopFeatureModel::TYPE_TEXT)
			{
				$features_with_values[$feature_id]['values'] = array();
				foreach ($values as $row)
				{
					$features_with_values[$feature_id]['values'][] = array(
						'value' => $row['id'],
						'label' => $row['value'],
					);
				}
			}
			elseif (strpos($type, shopFeatureModel::TYPE_RANGE) === 0)
			{
				$features_with_values[$feature_id] = $feature;
				unset ($features_with_values[$feature_id]['values']);
				$features_with_values[$feature_id]['is_range'] = true;
			}
			elseif ($type === shopFeatureModel::TYPE_COLOR)
			{
				$features_with_values[$feature_id]['values'] = array();
				foreach ($values as $row)
				{
					$features_with_values[$feature_id]['values'][] = array(
						'value' => '' . $row['id'],
						'label' => $row['value'],
					);
				}
			}
		}

		$this->view->assign('features_with_values', $features_with_values);
	}

	private function assignUnits()
	{
		$dimension = shopDimension::getInstance();
		$units = $dimension->getList();
		foreach (array_keys($units) as $type)
		{
			$units['_' . $type] = $units[$type];
			unset($units[$type]);
		}

		$this->view->assign('units', $units);
	}

	private function assignCustomTemplateMeta()
	{
		$meta = new shopSeofilterCustomTemplateVariablesMeta();

		$this->view->assign('custom_template_meta', $meta->getCustomTemplateVariablesMeta());
	}

	private function assignFilterFieldValues(shopSeofilterFilter $filter)
	{
		$this->view->assign('filter_fields', shopSeofilterFilterFieldModel::getAllFields());
		$this->view->assign('filter_fields_values', $filter->fields);
	}

	protected function assignFilterPersonalCanonicals(shopSeofilterFilter $filter)
	{
		$linkcanonical_is_enabled = shopSeofilterHelper::isLinkcanonicalPluginInstalled();

		if (!$linkcanonical_is_enabled)
		{
			$this->view->assign(array(
				'linkcanonical_is_enabled' => false,
				'filter_personal_canonicals' => array(),
			));

			return;
		}

		$canonical_list = array();

		foreach ($filter->canonicals as $canonical)
		{
			$canonical_attributes = $canonical->getAttributes();
			$canonical_attributes['is_enabled'] = $canonical->is_enabled == shopSeofilterFilterPersonalCanonical::ENABLED;

			$canonical_attributes['storefront_ids'] = $canonical->storefront_ids;
			$canonical_attributes['category_ids'] = $canonical->category_ids;

			$canonical_list[] = $canonical_attributes;
		}
		unset($canonical);

		$this->view->assign(array(
			'linkcanonical_is_enabled' => true,
			'filter_personal_canonicals' => $canonical_list,
		));
	}

	private function getStorefrontsCategories($storefront)
	{
		if (!strlen($storefront))
		{
			return null;
		}

		$model = new waModel();
		$sql = '
SELECT c.id category_id
FROM shop_category c
LEFT JOIN shop_category_routes cr ON cr.category_id = c.id
WHERE cr.category_id IS NULL OR cr.route = :storefront
';

		$rows = $model->query($sql, array('storefront' => $storefront));

		$category_ids = array();
		foreach ($rows as $row)
		{
			$category_ids[] = $row['category_id'];
		}
		return $category_ids;
	}
}