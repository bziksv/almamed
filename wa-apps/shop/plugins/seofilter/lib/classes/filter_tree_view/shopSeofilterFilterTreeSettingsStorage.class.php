<?php

class shopSeofilterFilterTreeSettingsStorage
{
	const STOREFRONT_SELECTION_MODE_ALL = 'ALL';
	const STOREFRONT_SELECTION_MODE_NONE = 'NONE';
	const STOREFRONT_SELECTION_MODE_LISTED = 'LISTED';

	const DB_TRUE = 'Y';
	const DB_FALSE = 'N';

	private $settings;

	private $model;
	private $category_model;
	private $filter_tree_category_settings_model;

	private $filter_tree_category_feature_settings_model;
	private $filter_storage;

	public function __construct()
	{
		$this->settings = shopSeofilterBasicSettingsModel::getSettings();

		$this->model = new waModel();
		$this->category_model = new shopCategoryModel();
		$this->filter_tree_category_settings_model = new shopSeofilterFilterTreeCategorySettingsModel();
		$this->filter_tree_category_feature_settings_model = new shopSeofilterFilterTreeCategoryFeatureSettingsModel();

		$this->filter_storage = new shopSeofilterFiltersStorage();
	}

	public function getCategoriesSettings()
	{
		$categories_settings = array();

		$categories_query = $this->category_model->select('*')->query();
		foreach ($categories_query as $category)
		{
			$categories_settings[] = $this->getShallowCategorySettings($category);
		}

		return $categories_settings;
	}

	public function getCategoryFeaturesSettings($category_id)
	{
		$category_storage = new shopSeofilterWaCategoryStorage();

		$feature_value_ids = $this->getFeatureValueIds($category_id);

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if ($settings->consider_category_filters)
		{
			$category_filter_feature_ids = array();

			foreach ($category_storage->getFilterFeatureIds($category_id) as $feature_id)
			{
				$category_filter_feature_ids[$feature_id] = $feature_id;
			}

			$feature_ids = array();
			foreach (array_keys($feature_value_ids) as $feature_id)
			{
				if (array_key_exists($feature_id, $category_filter_feature_ids))
				{
					$feature_ids[] = $feature_id;
				}
			}
		}
		else
		{
			$feature_ids = array_keys($feature_value_ids);
		}

		if (!count($feature_ids))
		{
			return array();
		}

		$result = array();

		$query = $this->filter_tree_category_feature_settings_model
			->select('storefront,feature_id,enabled_for_storefronts')
			->where('category_id = :category_id', array('category_id' => $category_id))
			->query();

		$feature_storefronts = array();
		foreach ($query as $row)
		{
			$storefront = $row['storefront'];
			$feature_id = $row['feature_id'];
			$storefront_selection_mode = $row['enabled_for_storefronts'];

			if (!array_key_exists($feature_id, $feature_storefronts))
			{
				$feature_storefronts[$feature_id] = array();
			}

			$feature_storefronts[$feature_id][$storefront] = $storefront_selection_mode;
		}

		foreach (shopSeofilterFilterFeatureValuesHelper::getFeatures('id', $feature_ids) as $feature)
		{
			$storefronts = array();
			if (array_key_exists($feature->id, $feature_storefronts))
			{
				if (array_key_exists('*', $feature_storefronts[$feature->id]))
				{
					$storefront_selection_mode = $feature_storefronts[$feature->id]['*'];
				}
				else
				{
					$storefront_selection_mode = self::STOREFRONT_SELECTION_MODE_LISTED;
					$storefronts = array_keys($feature_storefronts[$feature->id]);
				}
			}
			else
			{
				$storefront_selection_mode = self::STOREFRONT_SELECTION_MODE_ALL;
			}

			$result[] = array(
				'feature_id' => $feature->id,
				'feature_name' => $feature->name,
				'feature_code' => $feature->code,
				'storefront_selection' => array(
					'storefronts' => $storefronts,
					'selection_mode' => $storefront_selection_mode,
				)
			);
		}

		return $result;
	}

	public function getCategoryFeatureSettings($category_id, $feature_id)
	{
		$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureById($feature_id);
		if (!$feature)
		{
			return array();
		}

		$feature_type = $feature->type;

		try
		{
			$feature_value_model = shopFeatureModel::getValuesModel($feature_type);
			if (!$feature_value_model)
			{
				return array();
			}
		}
		catch (waException $e)
		{
			return array();
		}
		$feature_value_titles = array();

		if ($feature_value_model instanceof shopFeatureValuesBooleanModel)
		{
			$feature_value_titles[1] = 'Да';
			$feature_value_titles[0] = 'Нет';
		}
		elseif ($feature_value_model instanceof shopFeatureValuesDimensionModel)
		{
			/** @var shopDimensionValue[] $dimension_values */
			$dimension_values = $feature_value_model->getValues('feature_id', $feature_id);
			foreach ($dimension_values as $dimension_value)
			{
				$feature_value_titles[$dimension_value->id] = $dimension_value->value . ' ' . $dimension_value->unit_name;
			}
		}
		else
		{
			$feature_value_titles = $feature_value_model
				->select('id,value')
				->where('feature_id = :feature_id', array('feature_id' => $feature_id))
				->fetchAll('id', true);
		}

		$values_settings = array();

		$filters = array();

		foreach ($feature_value_titles as $value_id => $value_title)
		{
			$filter = $this->getFilterForFeatureValue($feature_id, $value_id);
			$filter_id = 0;
			$has_personal_rule = false;

			if ($filter)
			{
				$filter_id = $filter->id;

				$has_personal_rule = $this->getFilterCategoryPersonalRuleId($filter_id, $category_id, true) > 0;
				$filters[$filter_id] = array(
					'id' => $filter_id,
					'seo_name' => $filter->seo_name,
					'is_enabled' => !!$filter->is_enabled,
					'feature_id' => $feature_id,
					'value_id' => $value_id,
					'categories_has_personal_rule' => array(
						$category_id => $has_personal_rule,
					)
				);
			}

			$products_collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);
			$products_collection->filters(array(
				$feature->code => $value_id,
			));

			$values_settings[] = array(
				'value_id' => $value_id,
				'filter_id' => $filter_id,
				'has_personal_rule' => $has_personal_rule,
				'title' => $value_title,
				'is_enabled' => $filter && $this->isFilterEnabledForCategory($filter, $category_id),
				'products_count' => $products_collection->count(),
			);
		}

		return array(
			'feature_id' => $feature_id,
			'values_settings' => $values_settings,
			'filters' => $filters,
		);
	}





	public function updateCategoryState($category_is_enabled)
	{
		foreach ($category_is_enabled as $category_id => $is_enabled)
		{
			$this->filter_tree_category_settings_model->insert(array(
				'category_id' => $category_id,
				'storefront' => '*',
				'is_enabled' => $is_enabled ? self::DB_TRUE : self::DB_FALSE,
			), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}

	public function updateCategoryFeatureState($category_feature_is_enabled)
	{
		foreach ($category_feature_is_enabled as $category_id => $feature_is_enabled)
		{
			foreach ($feature_is_enabled as $feature_id => $is_enabled)
			{
				$this->filter_tree_category_feature_settings_model->insert(array(
					'category_id' => $category_id,
					'storefront' => '*',
					'feature_id' => $feature_id,
					'is_enabled' => $is_enabled ? self::DB_TRUE : self::DB_FALSE,
				), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
			}
		}
	}

	public function updateCategoryFeatureValueState($category_feature_value_is_enabled)
	{
		foreach ($category_feature_value_is_enabled as $category_id => $feature_value_is_enabled)
		{
			foreach ($feature_value_is_enabled as $feature_id => $value_is_enabled)
			{
				foreach ($value_is_enabled as $value_id => $is_enabled)
				{
					$filter = $this->getFilterForFeatureValue($feature_id, $value_id);

					if ($filter)
					{
						$this->toggleFilterForCategory($filter, $category_id, $is_enabled);
					}
					else
					{
					}
				}
			}
		}
	}

	public function updateCategoriesStorefrontSelection($categories_storefront_selection)
	{
		foreach ($categories_storefront_selection as $category_id => $storefront_selection)
		{
			$this->filter_tree_category_settings_model->deleteByField('category_id', $category_id);

			if ($storefront_selection['selection_mode'] == self::STOREFRONT_SELECTION_MODE_LISTED)
			{
				foreach ($storefront_selection['storefronts'] as $storefront)
				{
					$this->filter_tree_category_settings_model->insert(array(
						'storefront' => $storefront,
						'category_id' => $category_id,
						'enabled_for_storefronts' => self::STOREFRONT_SELECTION_MODE_LISTED,
					), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
				}
			}
			else
			{
				$mode = $storefront_selection['selection_mode'] == self::STOREFRONT_SELECTION_MODE_ALL
					? self::STOREFRONT_SELECTION_MODE_ALL
					: self::STOREFRONT_SELECTION_MODE_NONE;

				$this->filter_tree_category_settings_model->insert(array(
					'storefront' => '*',
					'category_id' => $category_id,
					'enabled_for_storefronts' => $mode,
				), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
			}
		}
	}

	public function updateCategoriesFeaturesStorefrontSelection($categories_features_storefront_selection)
	{
		foreach ($categories_features_storefront_selection as $category_id => $features_storefront_selection)
		{
			foreach ($features_storefront_selection as $feature_id => $storefront_selection)
			{
				$this->filter_tree_category_feature_settings_model->deleteByField(array(
					'category_id' => $category_id,
					'feature_id' => $feature_id,
				));

				if ($storefront_selection['selection_mode'] == self::STOREFRONT_SELECTION_MODE_LISTED)
				{
					foreach ($storefront_selection['storefronts'] as $storefront)
					{
						$this->filter_tree_category_feature_settings_model->insert(array(
							'storefront' => $storefront,
							'category_id' => $category_id,
							'feature_id' => $feature_id,
							'enabled_for_storefronts' => self::STOREFRONT_SELECTION_MODE_LISTED,
						), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
					}
				}
				else
				{
					$mode = $storefront_selection['selection_mode'] == self::STOREFRONT_SELECTION_MODE_ALL
						? self::STOREFRONT_SELECTION_MODE_ALL
						: self::STOREFRONT_SELECTION_MODE_NONE;

					$this->filter_tree_category_feature_settings_model->insert(array(
						'storefront' => '*',
						'category_id' => $category_id,
						'feature_id' => $feature_id,
						'enabled_for_storefronts' => $mode,
					), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
				}
			}
		}
	}

	public function updateFilterCategoryPersonalRule($filter_id, $category_id, $rule_params)
	{
		$rule = $this->getFilterCategoryEnabledPersonalRule($filter_id, $category_id);

		if (!$rule)
		{
			$rule = new shopSeofilterFilterPersonalRule();
			$rule->filter_id = $filter_id;
			$rule->categories_use_mode = shopSeofilterFilterPersonalRule::USE_MODE_LISTED;
			$rule->rule_categories = array($category_id);
		}

		$rule->setAttributes($rule_params);

		$rule->save();
	}

	public function deleteFilterCategoryPersonalRule($filter_id, $category_id)
	{
		$rule = $this->getFilterCategoryEnabledPersonalRule($filter_id, $category_id);

		if ($rule)
		{
			$rule->delete();
		}
	}

	public function getFilterCategoryEnabledPersonalRule($filter_id, $category_id)
	{
		$existing_rule_id = $this->getFilterCategoryPersonalRuleId($filter_id, $category_id, true);

		$ar = new shopSeofilterFilterPersonalRule();

		return $existing_rule_id > 0
			? $ar->getById($existing_rule_id)
			: null;
	}

	public function getFilterCategoryPersonalRule($filter_id, $category_id)
	{
		$existing_rule_id = $this->getFilterCategoryPersonalRuleId($filter_id, $category_id, false);

		$ar = new shopSeofilterFilterPersonalRule();

		return $existing_rule_id > 0
			? $ar->getById($existing_rule_id)
			: null;
	}

	/**
	 * @param int $feature_id
	 * @param int $value_id
	 * @return shopSeofilterFilter|null
	 */
	public function getFilterForFeatureValue($feature_id, $value_id)
	{
		$sql = '
SELECT f.id, f.is_enabled
FROM shop_seofilter_filter AS f
	JOIN shop_seofilter_filter_feature_value AS fv
		ON f.id = fv.filter_id
WHERE f.feature_values_count = 1
	AND f.feature_value_ranges_count = 0
	AND fv.feature_id = :feature_id
	AND fv.value_id = :value_id
';

		$query_params = array(
			'feature_id' => $feature_id,
			'value_id' => $value_id,
		);

		$filter_is_enabled = $this->model->query($sql, $query_params)->fetchAll('id', true);
		$filter_ids = array_keys($filter_is_enabled);

		if (count($filter_ids) == 0)
		{
			return null;
		}

		$returned_filter_id = null;
		if (count($filter_ids) == 1)
		{
			$returned_filter_id = reset($filter_ids);
		}
		else
		{
			foreach ($filter_is_enabled as $filter_id => $is_enabled)
			{
				if ($is_enabled)
				{
					$returned_filter_id = $filter_id;

					break;
				}
			}

			if (!$returned_filter_id)
			{
				$returned_filter_id = reset($filter_ids);
			}
		}

		return $this->filter_storage->getById($returned_filter_id);
	}

	public function isPluginEnabledOnStorefrontCategory($storefront, $category_id)
	{
		$settings = $this->filter_tree_category_settings_model
			->select('storefront,enabled_for_storefronts')
			->where('category_id = :id', array('id' => $category_id))
			->fetchAll('storefront', true);

		if (count($settings) == 0)
		{
			return true;
		}
		elseif (array_key_exists('*', $settings))
		{
			return $settings['*'] == self::STOREFRONT_SELECTION_MODE_ALL;
		}
		elseif (array_key_exists($storefront, $settings))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function isPluginEnabledOnStorefrontCategoryFeature($storefront, $category_id, $feature_id)
	{
		$select = $this->filter_tree_category_feature_settings_model
			->select('storefront,enabled_for_storefronts')
			->where('category_id = :category_id', array('category_id' => $category_id));



		$settings = $select
			->where('feature_id = :feature_id', array('feature_id' => $feature_id))
			->fetchAll('storefront', true);

		if (count($settings) == 0)
		{
			return true;
		}
		elseif (array_key_exists('*', $settings))
		{
			return $settings['*'] == self::STOREFRONT_SELECTION_MODE_ALL;
		}
		elseif (array_key_exists($storefront, $settings))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function getShallowCategorySettings($category)
	{
		$category_storefronts = $this->filter_tree_category_settings_model
			->select('storefront,enabled_for_storefronts')
			->where('category_id = :id', array('id' => $category['id']))
			->fetchAll('storefront', true);

		$storefronts = array();
		if (array_key_exists('*', $category_storefronts))
		{
			$storefront_selection_mode = $category_storefronts['*'];
		}
		elseif (count($category_storefronts))
		{
			$storefront_selection_mode = self::STOREFRONT_SELECTION_MODE_LISTED;
			$storefronts = array_keys($category_storefronts);
		}
		else
		{
			$storefront_selection_mode = self::STOREFRONT_SELECTION_MODE_ALL;
		}

		return array(
			'category_id' => $category['id'],
			'category' => $category,
			'storefront_selection' => array(
				'storefronts' => $storefronts,
				'selection_mode' => $storefront_selection_mode,
			)
		);
	}

	private function getFeatureValueIds($category_id)
	{
		$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);
		$feature_value_ids = $collection->getFeatureValueIds();

		$features = shopSeofilterFilterFeatureValuesHelper::getPossibleFilterFeatures();
		foreach (array_keys($feature_value_ids) as $feature_id)
		{
			if (!array_key_exists($feature_id, $features))
			{
				unset($feature_value_ids[$feature_id]);
			}
		}

		return $feature_value_ids;
	}

	private function isFilterEnabledForCategory(shopSeofilterFilter $filter, $category_id)
	{
		return $filter->is_enabled && $this->isFilterAppliedToCategory($filter, $category_id);
	}

	private function isFilterAppliedToCategory(shopSeofilterFilter $filter, $category_id)
	{
		if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_LISTED
			&& !in_array(
				$category_id,
				$filter->filter_categories
			))
		{
			return false;
		}

		if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT
			&& in_array(
				$category_id,
				$filter->filter_categories
			))
		{
			return false;
		}

		return true;
	}

	private function toggleFilterForCategory(shopSeofilterFilter $filter, $category_id, $is_enabled)
	{
		$filter_category_ids = array();
		foreach ($filter->filter_categories as $filter_category_id)
		{
			$filter_category_ids[$filter_category_id] = $filter_category_id;
		}

		if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_ALL && !$is_enabled)
		{
			$filter->filter_categories = array($category_id);

			$filter->categories_use_mode = shopSeofilterFilter::USE_MODE_EXCEPT;
		}
		elseif (
			($is_enabled && $filter->categories_use_mode == shopSeofilterFilter::USE_MODE_LISTED)
			|| (!$is_enabled && $filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT)
		)
		{
			$filter_category_ids[$category_id] = $category_id;

			$filter->filter_categories = array_values($filter_category_ids);
		}
		elseif (
			(!$is_enabled && $filter->categories_use_mode == shopSeofilterFilter::USE_MODE_LISTED)
			|| ($is_enabled && $filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT)
		)
		{
			unset($filter_category_ids[$category_id]);

			$filter->filter_categories = array_values($filter_category_ids);
		}
		else
		{
			return true;
		}

		if (count($filter->filter_categories) == 0)
		{
			if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT)
			{
				$filter->categories_use_mode = shopSeofilterFilter::USE_MODE_ALL;
			}
		}

		return $filter->save();
	}

	private function getFilterCategoryPersonalRuleId($filter_id, $category_id, $enabled_only)
	{
		$sql = '
SELECT rc.rule_id
FROM shop_seofilter_filter_personal_rule AS r
	JOIN shop_seofilter_filter_personal_rule_category AS rc
		ON rc.rule_id = r.id
WHERE r.filter_id = :filter_id AND r.categories_use_mode = :use_mode_listed';

		if ($enabled_only)
		{
			$sql .= ' AND r.is_enabled = :is_enabled';
		}

		$sql .= PHP_EOL . 'GROUP BY rc.rule_id
HAVING COUNT(rc.category_id) = 1 AND MAX(rc.category_id) = :category_id
ORDER BY rc.rule_id DESC
LIMIT 1
';

		$params = array(
			'filter_id' => $filter_id,
			'category_id' => $category_id,
			'use_mode_listed' => shopSeofilterFilterPersonalRule::USE_MODE_LISTED,
			'is_enabled' => shopSeofilterFilterPersonalRule::ENABLED,
		);

		return $this->model->query($sql, $params)->fetchField();
	}
}
