<?php

class shopBreadcrumbsSeofilterBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	/** @var shopProduct */
	private $product;
	private $features;

	private $product_feature_values;
	private $product_features_model;
	private $product_seofilter_item_mode;

	public function __construct($product, $features, $product_seofilter_item_mode)
	{
		$this->product = $product;
		$this->features = $features;
		$this->product_seofilter_item_mode = $product_seofilter_item_mode;

		$this->product_feature_values = null;
		$this->product_features_model = new shopProductFeaturesModel();
	}

	protected function initializeItems()
	{
		$helper = new shopBreadcrumbsHelper();

		if (
			!$helper->isSeofilterPluginEnabled()
			|| !$this->product
			|| !is_array($this->features)
			|| !count($this->features)
		)
		{
			return array();
		}

		$category_model = new shopCategoryModel();
		$category = $category_model->getById($this->product['category_id']);

		if (!$category)
		{
			return array();
		}

		$categories_path = $this->getCategoriesPath($category);
		if ($this->isPathContainsHiddenCategories($categories_path))
		{
			return array();
		}


		$category_id = $category['id'];

		$items = array();
		$filters_storage = new shopBreadcrumbsSeofilterFiltersFrontendStorage();

		foreach ($this->features as $feature)
		{
			foreach ($this->getProductFeatureValues($feature) as $value)
			{
				$filter_params = array(
					$feature['code'] => array($value['id'])
				);

				$filter = $filters_storage->getByFilterParams(
					shopBreadcrumbsPlugin::getStorefront(),
					$category_id,
					$filter_params,
					''
				);

				if ($filter && $filter->countProducts($category_id, ''))
				{
					$name = $value['name'];

					if ($this->product_seofilter_item_mode == shopBreadcrumbsSettings::PRODUCT_SEOFILTER_ITEM_MODE_FEATURE_NAME)
					{
						$name = $value['name'];
					}
					elseif ($this->product_seofilter_item_mode == shopBreadcrumbsSettings::PRODUCT_SEOFILTER_ITEM_MODE_CATEGORY_AND_FEATURE_NAME)
					{
						$name = $category['name'] . ' ' . $value['name'];
					}
					elseif ($this->product_seofilter_item_mode == shopBreadcrumbsSettings::PRODUCT_SEOFILTER_ITEM_MODE_SEO_NAME)
					{
						$name = $filter->seo_name;
					}
					elseif ($this->product_seofilter_item_mode == shopBreadcrumbsSettings::PRODUCT_SEOFILTER_ITEM_MODE_FILTER_H1)
					{
						$seofilter_extender = new shopBreadcrumbsSeofilterExtender();
						$name = $seofilter_extender->getFilterH1($category_id, $filter);
					}

					$items[] = array(
						'name' => $name,
						'url' => $filter->getFrontendCategoryUrl($this->product['category_id']),
					);
				}
			}
		}

		return $items;
	}

	private function getProductFeatureValues($feature)
	{
		if ($this->product_feature_values === null)
		{
			$this->product_feature_values = $this->getProductsFeatures();
		}

		$values = ifset($this->product_feature_values[$feature['code']]);
		$values_with_name = array();

		if ($values === null)
		{
			return array();
		}
		elseif (!is_array($values))
		{
			$values = array($values);
		}

		foreach ($values as $value_id => $value)
		{
			$name = null;

			if ($value instanceof shopColorValue)
			{
				$name = $value->value;
			}
			elseif ($value instanceof shopDimensionValue)
			{
				$name = $value->value . ' ' . $value->unit_name;
			}
			elseif ($value instanceof shopBooleanValue && $value->value)
			{
				$name = $feature['name'];
			}
			elseif (is_string($value))
			{
				$name = $value;
			}

			if ($name !== null)
			{
				$values_with_name[] = array(
					'id' => $value_id,
					'name' => $name,
				);
			}
		}

		return $values_with_name;
	}


	private function getProductsFeatures()
	{
		$public_only = false;

		$product = $this->product;

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
			$type = preg_replace('/\..*$/', '', $f['type']);
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
		foreach ($type_values as $type => $value_ids)
		{
			$model = shopFeatureModel::getValuesModel($type);
			$type_values[$type] = $model->getValues('id', $value_ids);
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
					if (is_array($value_ids))
					{
						$features_prepared[$f['code']] = array();
						//keep feature values order
						foreach (ifset($type_values, $type, $feature_id, array()) as $v_id => $v_value)
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

	private function getCategoriesPath($current_category)
	{
		$model = new shopCategoryModel();
		$path = array($current_category);

		$category_id = $current_category['parent_id'];
		while ($category_id > 0)
		{
			$category = $model->getById($category_id);
			if (!$category)
			{
				break;
			}

			$path[] = $category;
			$category_id = $category['parent_id'];
		}

		return array_reverse($path);
	}

	private function isPathContainsHiddenCategories($path)
	{
		$category_ids = array();
		foreach ($path as $category)
		{
			$category_ids[] = $category['id'];
		}


		$storefront = shopBreadcrumbsPlugin::getStorefront();

		$category_routes_model = new shopCategoryRoutesModel();
		$routes = $category_routes_model->getRoutes($category_ids);
		foreach ($path as $c)
		{
			if (isset($routes[$c['id']]) && !in_array($storefront, $routes[$c['id']]))
			{
				return true;
			}
		}

		return false;
	}
}