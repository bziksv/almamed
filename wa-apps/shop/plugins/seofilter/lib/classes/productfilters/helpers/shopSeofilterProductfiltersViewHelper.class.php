<?php

class shopSeofilterProductfiltersViewHelper
{
	private static $storefront = false;
	private static $currency = false;

	private static $categories = array();
	private static $products = array();
	private static $products_feature_values = array();

	/** @var shopSeofilterProductfiltersSettings */
	private static $productfilters_settings = null;
	/** @var shopSeofilterPluginSettings */
	private static $seofilter_settings = null;
	/** @var shopSeofilterProductfiltersCategoryFeatureRules */
	private static $category_features_rule = null;

	public static function getValueUrl($feature_code, $value, $product = null)
	{
		$product = self::prepareProduct($product);
		if (!$product)
		{
			return null;
		}

		self::init($product);

		$category = ifset(self::$categories[$product['category_id']]);
		if (!$category || !self::$productfilters_settings->is_enabled)
		{
			return null;
		}

		$category_settings = new shopSeofilterProductfiltersCategorySettings($category['id']);
		if (!$category_settings->is_enabled)
		{
			return null;
		}

		$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureByCode($feature_code);

		$product_feature_values = ifset(self::$products_feature_values[$product['id']]);
		if (!$feature || !is_array($product_feature_values) || !isset($product_feature_values[$feature_code]))
		{
			return null;
		}

		$value_id = self::getValueId($product_feature_values[$feature_code], $value);
		if (!$value_id)
		{
			return null;
		}

		$filter_params = array(
			$feature_code => array($value_id),
		);

		$rule_part = self::$category_features_rule->getRulePart(self::$storefront, $category['id'], $feature->id);
		if ($rule_part && !$rule_part['display_link'])
		{
			return null;
		}

		$link_category = $rule_part && $rule_part['link_category_id']
			? self::getCategory($rule_part['link_category_id'])
			: $category;

		if (!$link_category)
		{
			$link_category = $category;
		}

		if (self::$seofilter_settings->is_enabled)
		{
			$storage = new shopSeofilterFiltersFrontendStorage();
			$filter = $storage->getByFilterParams(
				self::$storefront,
				$link_category['id'],
				$filter_params,
				self::$currency
			);

			// todo посмотреть сколько проблем вылезет если не проверять наличие товара
			//if ($filter && $filter->countProducts($link_category['id'], self::$currency))
			if ($filter)
			{
				return $filter->getFrontendCategoryUrl($link_category);
			}
		}

		return null;
	}

	public static function getValueLinkHtml($feature_code, $value, $product = null)
	{
		$product = self::prepareProduct($product);

		if (!$product)
		{
			return $value;
		}

		$url = self::getValueUrl($feature_code, $value);

		if (!$url)
		{
			return $value;
		}

		return self::wrapValue($value, $url);
	}

	public static function wrapFeatureValues($product_feature_values, $product = null)
	{
		if (!$product)
		{
			$product = self::prepareProduct($product);
		}

		if (!$product)
		{
			return $product_feature_values;
		}

		foreach ($product_feature_values as $feature_code => $values)
		{
			if (is_array($values))
			{
				foreach ($values as $index => $value)
				{
					$url = self::getValueUrl($feature_code, $value, $product);
					if ($url)
					{
						$product_feature_values[$feature_code][$index] = self::wrapValue($value, $url);
					}
				}
			}
			else
			{
				$value = $values;
				$url = self::getValueUrl($feature_code, $value, $product);
				if ($url)
				{
					$product_feature_values[$feature_code] = self::wrapValue($value, $url);
				}
			}
		}

		return $product_feature_values;
	}

	public static function getValueCombinationUrls($values_count, $product = null)
	{
		$product = self::prepareProduct($product);
		if (!$product)
		{
			return array();
		}

		self::init($product);

		$collection = new shopSeofilterProductFiltersCollection($product);

		if ($values_count > 0)
		{
			$collection->filterFilterFeatureValuesCount($values_count);
		}

		$category_filters = $collection
			->setStorefront(self::$storefront)
			->getCategoryFilters();

		$added_filter_ids = array();

		$result = array();
		foreach ($category_filters as $category_id => $filters)
		{
			$category = self::getCategory($category_id);
			if (!$category)
			{
				continue;
			}

			/** @var shopSeofilterFilter $filter */
			foreach ($filters as $filter)
			{
				if (!array_key_exists($filter->id, $added_filter_ids))
				{
					$result[] = array(
						'seo_name' => $filter->seo_name,
						'url' => $filter->getFrontendCategoryUrl($category),
					);

					$added_filter_ids[$filter->id] = true;
				}
			}
		}

		return $result;
	}

	public static function getAllProductFilterUrls($product = null)
	{
		$product = self::prepareProduct($product);
		if (!$product)
		{
			return array();
		}

		self::init($product);

		$collection = new shopSeofilterProductFiltersCollection($product);

		$category_filters = $collection
			->setStorefront(self::$storefront)
			->getCategoryFilters();

		$added_filter_ids = array();

		$result = array();
		foreach ($category_filters as $category_id => $filters)
		{
			$category = self::getCategory($category_id);
			if (!$category)
			{
				continue;
			}

			/** @var shopSeofilterFilter $filter */
			foreach ($filters as $filter)
			{
				if (!array_key_exists($filter->id, $added_filter_ids))
				{
					$result[] = array(
						'seo_name' => $filter->seo_name,
						'url' => $filter->getFrontendCategoryUrl($category),
					);

					$added_filter_ids[$filter->id] = true;
				}
			}
		}

		return $result;
	}

	public static function targetIsBlank()
	{
		return self::$productfilters_settings->open_link_in_new_tab == shopSeofilterProductfiltersSettings::OPEN_LINK_IN_NEW_TAB;
	}

	public static function getCustomLinkText()
	{
		if (self::$productfilters_settings->link_type == shopSeofilterProductfiltersSettings::LINK_TYPE_VALUE)
		{
			return '';
		}
		elseif (self::$productfilters_settings->link_type == shopSeofilterProductfiltersSettings::LINK_TYPE_OTHER_PRODUCTS)
		{
			return 'другие товары';
		}
		elseif (self::$productfilters_settings->link_type == shopSeofilterProductfiltersSettings::LINK_TYPE_CUSTOM_TEXT)
		{
			return is_string(self::$productfilters_settings->custom_link_text)
				? trim(self::$productfilters_settings->custom_link_text)
				: '';
		}
		else
		{
			return '';
		}
	}

	private static function wrapValue($value, $url)
	{
		$url_esc = htmlentities($url);

		$target = self::targetIsBlank()
			? 'target="_blank"'
			: '';

		$custom_link_text = self::getCustomLinkText();
		if ($custom_link_text === '')
		{
			return "<a href=\"{$url_esc}\" class=\"productfilters-feature-value-link\" {$target}>{$value}</a>";
		}
		else
		{
			return "{$value} (<a href=\"{$url_esc}\" class=\"productfilters-feature-value-link_other-products\" {$target}>{$custom_link_text}</a>)";
		}
	}

	/**
	 * @param $product_values
	 * @param $searched_value
	 * @return int|null
	 */
	private static function getValueId($product_values, $searched_value)
	{
		if (is_string($searched_value))
		{
			$searched_value = trim($searched_value);

			foreach ($product_values as $value_id => $value)
			{
				if ($searched_value === trim($value))
				{
					return $value_id;
				}
			}
		}
		elseif ($searched_value instanceof shopColorValue || $searched_value instanceof shopDimensionValue)
		{
			return $searched_value->id;
		}
		elseif ($searched_value instanceof shopBooleanValue)
		{
			return $searched_value->value;
		}

		return null;
	}

	private static function init($product)
	{
		$product_id = $product['id'];

		self::$products[$product_id] = $product;

		if (!isset(self::$products_feature_values[$product_id]))
		{
			self::$products_feature_values[$product_id] = self::getProductsFeatures($product);
		}

		$category_id = $product['category_id'];
		self::getCategory($category_id);

		if (self::$productfilters_settings === null)
		{
			self::$productfilters_settings = new shopSeofilterProductfiltersSettings();
			self::$seofilter_settings = shopSeofilterBasicSettingsModel::getSettings();
			self::$category_features_rule = new shopSeofilterProductfiltersCategoryFeatureRules();
			self::$storefront = shopSeofilterProductfiltersHelper::getStorefront();
			self::$currency = shopSeofilterProductfiltersHelper::getCurrency();
		}
	}

	private static function prepareProduct($product)
	{
		if (!$product)
		{
			/** @var array $product */
			$product = wa()->getView()->getVars('product');
		}

		return $product;
	}

	private static function getProductsFeatures($product)
	{
		if (!$product)
		{
			return array();
		}

		$public_only = false;

		$product_features_model = new shopProductFeaturesModel();
		$product_id = $product['id'];

		$rows = $product_features_model->getByField(
			array(
				'product_id' => $product_id,
				'sku_id' => null,
			),
			true
		);



		if ($product['sku_type'])
		{
			$sql = 'SELECT pf.* FROM shop_product_features pf
                    JOIN shop_product_features_selectable pfs ON pf.product_id = pfs.product_id AND pf.feature_id = pfs.feature_id
                    WHERE pf.sku_id IS NOT NULL AND pf.product_id = i:id';
			$rows = array_merge(
				$rows,
				$product_features_model->query($sql, array('id' => $product_id))->fetchAll()
			);
		}
		if (!$rows)
		{
			return array();
		}

		$tmp = array();
		foreach ($rows as $row)
		{
			$tmp[$row['feature_id']] = true;
		}
		$feature_model = new shopFeatureModel();
		$sql = 'SELECT * FROM ' . $feature_model->getTableName() . " WHERE id IN (i:ids) OR type = 'divider'";
		$features = $feature_model->query($sql, array('ids' => array_keys($tmp)))->fetchAll('id');

		$type_values = $product_features = array();
		foreach ($rows as $row)
		{
			if (empty($features[$row['feature_id']]))
			{
				continue;
			}
			$f = $features[$row['feature_id']];
			if ($public_only && $f['status'] != 'public')
			{
				unset($features[$row['feature_id']]);
				continue;
			}

			$type = $f['type'];
			if (strpos($type, '.') !== false)
			{
				$type_parts = explode('.', $type);
				$type = $type_parts[count($type_parts) - 2];
			}

			if ($type != shopFeatureModel::TYPE_BOOLEAN && $type != shopFeatureModel::TYPE_DIVIDER)
			{
				$type_values[$type][$row['feature_value_id']] = $row['feature_value_id'];
			}
			if ($f['multiple'])
			{
				$product_features[$row['product_id']][$f['id']][$row['feature_value_id']] = $row['feature_value_id'];
			}
			else
			{
				$product_features[$row['product_id']][$f['id']] = $row['feature_value_id'];
			}
		}
		foreach (array_keys($type_values) as $type)
		{
			$value_ids = $type_values[$type];
			try
			{
				$model = shopFeatureModel::getValuesModel($type);
				if (!$model)
				{
					continue;
				}

				$type_values[$type] = $model->getValues('id', $value_ids);
			}
			catch (waException $e)
			{
			}
		}


		$features_prepared = array();

		foreach ($features as $feature_id => $f)
		{
			$type = preg_replace('/\..*$/', '', $f['type']);

			if (isset($product_features[$product['id']][$feature_id]))
			{
				$value_ids = $product_features[$product['id']][$feature_id];

				if ($type == shopFeatureModel::TYPE_BOOLEAN || $type == shopFeatureModel::TYPE_DIVIDER)
				{
					/**
					 * @var shopFeatureValuesBooleanModel|shopFeatureValuesDividerModel $model
					 */
					$model = shopFeatureModel::getValuesModel($type);
					$values = $model->getValues('id', $value_ids);

					if (is_array($values))
					{
						$features_prepared[$f['code']] = $values;
					}
					else
					{
						$features_prepared[$f['code']] = array(
							$value_ids => $values,
						);
					}
				}
				else
				{
					if (!array_key_exists($type, $type_values) || !array_key_exists($feature_id, $type_values[$type]))
					{
						continue;
					}

					if (is_array($value_ids))
					{
						$features_prepared[$f['code']] = array();
						//keep feature values order
						foreach ($type_values[$type][$feature_id] as $v_id => $v_value)
						{
							if (in_array($v_id, $value_ids))
							{
								$features_prepared[$f['code']][$v_id] = $v_value;
							}
						}
					}
					elseif (isset($type_values[$type][$feature_id][$value_ids]))
					{
						$_features = $features_prepared;
						$_features[$f['code']] = array(
							$value_ids => $type_values[$type][$feature_id][$value_ids],
						);
						$features_prepared = $_features;
					}
				}
			}
			elseif ($type == shopFeatureModel::TYPE_DIVIDER)
			{
				$features_prepared[$f['code']] = '';
			}
		}

		return $features_prepared;
	}

	private static function getCategory($category_id)
	{
		if (!isset(self::$categories[$category_id]))
		{
			$category_model = new shopCategoryModel();
			self::$categories[$category_id] = $category_model->getById($category_id);
		}

		return self::$categories[$category_id];
	}
}
