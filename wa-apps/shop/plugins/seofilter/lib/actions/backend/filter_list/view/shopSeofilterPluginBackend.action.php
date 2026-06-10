<?php

class shopSeofilterPluginBackendAction extends shopSeofilterBackendViewAction
{
	public function execute()
	{
		$this->setTemplate('FilterList');

		$this->left_sidebar['pages']['all']['current'] = true;

		$this->addCss(array(
			'shop.css',
			'list.css',
			'react-select.css',
			'react-popup.css',
			'bs_ui.css',
		));

		$this->assignRightSidebar();

		$backend_filters_list_page = new shopSeofilterBackendFiltersListPage();

		$this->assignSort($backend_filters_list_page->getSort(), $backend_filters_list_page->getOrder());
		$this->assignFilters($backend_filters_list_page->getFilter(), $backend_filters_list_page->getFilterPartial());

		$this->assignFilterUseModeOptions();
		$this->assignAllCategoriesAndStorefronts();
		$this->assignFeatures();
		$this->assignGeneratorHistory();
		$this->assignFeaturesWithValues();


		$filters_list = $backend_filters_list_page->getFiltersList();

		$this->view->assign(array(
			'filters' => $filters_list['filters'],
			'pagination' => $filters_list['pagination'],
			'total_count' => $filters_list['total_count'],
			'current_page' => $backend_filters_list_page->getPage(),
			'per_page' => shopSeofilterPagination::itemsPerPage()
		));
	}

	private function assignRightSidebar()
	{
		$mass_actions_sidebar = array(
			array(
				'text' => 'Включить',
				'action' => 'enable',
				'icon_class' => 'yes',
			),
			array(
				'text' => 'Выключить',
				'action' => 'disable',
				'icon_class' => 'no',
			),
			array(
				'text' => 'Удалить',
				'action' => 'delete',
				'icon_class' => 'delete',
				'confirm' => 'Вы действительно хотите удалить выбранные фильтры?',
			),
		);
		$this->view->assign('mass_actions_sidebar', $mass_actions_sidebar);
	}

	private function assignFilterUseModeOptions()
	{
		$use_mode_options = array(
			array(
				'value' => shopSeofilterFilter::USE_MODE_ALL,
				'title' => 'Все',
			),
			array(
				'value' => shopSeofilterFilter::USE_MODE_LISTED,
				'title' => 'Только для',
			),
			array(
				'value' => shopSeofilterFilter::USE_MODE_EXCEPT,
				'title' => 'Для всех, кроме',
			),
		);
		$this->view->assign('use_mode_options', $use_mode_options);
	}

	private function assignGeneratorHistory()
	{
		$history_ar = new shopSeofilterGeneratorHistory();

		$history = array();
		/** @var shopSeofilterGeneratorHistory $history_item */
		foreach ($history_ar->getAllNotEmpty() as $history_item)
		{
			$history[] = $history_item->getViewAttributes();
		}

		$this->view->assign('generator_history', $history);
	}

	private function assignFeatures()
	{
		$features = array();

		$model = new shopFeatureModel();

		$rows = $model
			->select('*')
			->where("(`selectable`=1 OR `type`='boolean' OR `type`='double' OR `type` LIKE 'dimension.%')")
			->where('`parent_id` IS NULL')
			->where("`type` NOT LIKE '2d.%'")
			->where("`type` NOT LIKE '3d.%'")
			->query();

		foreach ($rows as $feature)
		{
			$features[] = array(
				'label' => $feature['name'],
				'value' => $feature['id'],
				'hint' => $feature['code'],
			);
		}

		$this->view->assign('features', $features);
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


		$storefronts = array();
		foreach (wa()->getRouting()->getByApp('shop') as $domain => $route_attributes_arr)
		{
			foreach ($route_attributes_arr as $route_attributes)
			{
				$storefront = $domain . '/' . $route_attributes['url'];
				$storefronts[] = array(
					'value' => $storefront,
					'label' => trim($storefront, '*/'),
				);
			}
		}

		$this->view->assign('all_categories', $list_ordered);
		$this->view->assign('category_names', $category_names);
		$this->view->assign('all_storefronts', $storefronts);
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

	private function assignSort($sort, $order)
	{
		$filter_storefront = new shopSeofilterFilterStorefront();
		$is_without_storefront_column = $filter_storefront->countDistinct('storefront') <= 1;

		$sort_columns = array();
		foreach (shopSeofilterFilter::getSortColumns() as $column => $name)
		{
			if ($is_without_storefront_column && $column === 'storefront')
			{
				$sort = $sort === $column ? shopSeofilterFilter::DEFAULT_SORT : $sort;
				continue;
			}

			$order_for_url = $sort === $column
				? ($order === 'asc' ? 'desc' : 'asc')
				: shopSeofilterFilter::DEFAULT_ORDER;

			$href = "?plugin=seofilter&sort={$column}&order={$order_for_url}";

			$is_highlighted = $sort !== shopSeofilterFilter::DEFAULT_SORT || $order !== shopSeofilterFilter::DEFAULT_ORDER;

			$sort_columns[$column] = array(
				'name' => $name,
				'href' => $href,
				'is_highlighted' => $is_highlighted,
			);
		}

		$this->view->assign(
			array(
				'sort' => $sort,
				'order' => $order,
				'sort_columns' => $sort_columns,
				'is_without_storefront_column' => $is_without_storefront_column,
			)
		);
	}

	private function assignFilters($filter, $filter_partial)
	{
		$filter_ar = new shopSeofilterFilter();
		if ($filter_ar->countAll() == 0)
		{
			$this->view->assign('filter_name_value', '');
			$this->view->assign('select_filters', array());
			$this->view->assign('select_filter_values', array());

			$this->view->assign('current_table_filtration',
				array(
					'name' => '',
					'url' => '',
					'filter_values' => array(),
					'show_corrupted_filters' => '0',
				)
			);

			return;
		}

		$name_current_value = isset($filter_partial['seo_name'])
			? trim($filter_partial['seo_name'], '%')
			: '';
		$url_current_value = isset($filter_partial['url'])
			? trim($filter_partial['url'], '%')
			: '';

		$show_corrupted_filters = isset($filter['show_corrupted_filters'])
			? $filter['show_corrupted_filters']
			: '0';

		$select_filters = array();
		$select_filter_values = array();

		if (isset($filter['features']) && is_array($filter['features']) && count($filter['features']))
		{
			$select_filter_values['features'] = $filter['features'][0];
		}
		if (isset($filter['feature_values_count']) && is_array($filter['feature_values_count']) && count($filter['feature_values_count']))
		{
			$select_filter_values['feature_values_count'] = $filter['feature_values_count'][0];
		}

		$storefronts = array();

		$rule_storefront = new shopSeofilterFilterStorefront();
		$distinct_storefronts = $rule_storefront->getDistinct('storefront');
		foreach ($distinct_storefronts as $row)
		{
			$storefront = $row['storefront'];
			if (empty($storefront))
			{
				continue;
			}

			$storefronts[] = array(
				'title' => rtrim($storefront, '*/'),
				'value' => $storefront,
			);
		}
		$select_filters[] = array(
			'id' => 'filter_storefront',
			'title' => 'Витрина',
			'name' => 'storefront',
			'options' => $storefronts,
		);


		$feature_options = array();
		$feature_model = new shopFeatureModel();
		$rows = $feature_model
			->select('*')
			->where("(`selectable`=1 OR `type`='boolean' OR `type`='double' OR `type` LIKE 'dimension.%')")
			->where('`parent_id` IS NULL')
			->where("`type` NOT LIKE '2d.%'")
			->where("`type` NOT LIKE '3d.%'")
			->query();
		foreach ($rows as $feature)
		{
			$feature_options[] = array(
				'title' => $feature['name'],
				'value' => $feature['id'],
			);
		}
		$select_filters[] = array(
			'id' => 'filter_features',
			'title' => 'Характеристика',
			'name' => 'features',
			'options' => $feature_options,
		);


		$count_options = array();
		$model = new waModel();
		$sql = '
SELECT COALESCE(MAX(t.c), 0) `max`
FROM (
	select count(DISTINCT f_fv.id) + count(DISTINCT f_fvr.id) c
	from shop_seofilter_filter f
	left join shop_seofilter_filter_feature_value f_fv on f.id = f_fv.filter_id
	left join shop_seofilter_filter_feature_value_range f_fvr on f.id = f_fvr.filter_id
	GROUP BY f.id
) t
';
		$max = (int)$model->query($sql)->fetchField();

		if ($max)
		{
			for ($i = 1; $i <= $max; $i++)
			{
				$count_options[] = array(
					'title' => "$i",
					'value' => "$i",
				);
			}
		}

		$select_filters[] = array(
			'id' => 'filter_feature_values_count',
			'title' => 'Кол-во характеристик',
			'name' => 'feature_values_count',
			'options' => $count_options,
		);

		$this->view->assign('select_filters', $select_filters);
		$this->view->assign('current_table_filtration',
			array(
				'name' => $name_current_value,
				'url' => $url_current_value,
				'filter_values' => $select_filter_values,
				'show_corrupted_filters' => $show_corrupted_filters,
			)
		);
	}
}
