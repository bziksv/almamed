<?php

class shopSeofilterFilterFeatureValuesHelper
{
	private static $features_by_id = null;
	private static $features_by_code = null;
	private static $possible_features = null;

	private static $existing_codes = array(
		'price' => 1,
		'price_min' => 1,
		'price_max' => 1,
	);
	private static $not_existing_codes = array(
		'price' => 1,
		'sort' => 1,
		'order' => 1,
		'page' => 1,
		'_' => 1,
	);
	private static $boolean_feature_codes = array();

	/**
	 * @param $feature_id
	 * @return shopSeofilterFeature|null
	 */
	public static function getFeatureById($feature_id)
	{
		self::loadAllFeatures();

		return array_key_exists($feature_id, self::$features_by_id)
			? self::$features_by_id[$feature_id]
			: null;
	}

	/**
	 * @param $feature_code
	 * @return shopSeofilterFeature|null
	 */
	public static function getFeatureByCode($feature_code)
	{
		self::loadAllFeatures();

		return array_key_exists($feature_code, self::$features_by_code)
			? self::$features_by_code[$feature_code]
			: null;
	}

	/**
	 * @param string $field 'id' or 'code'
	 * @param int|string|int[]|string[] $values
	 * @param $group_key
	 * @return shopSeofilterFeature[]
	 * @throws waException
	 */
	public static function getFeatures($field, $values, $group_key = null)
	{
		if (!is_array($values))
		{
			$values = array($values);
		}

		if ($field === 'id')
		{
			$get_feature_function = array('shopSeofilterFilterFeatureValuesHelper', 'getFeatureById');
		}
		elseif ($field === 'code')
		{
			$get_feature_function = array('shopSeofilterFilterFeatureValuesHelper', 'getFeatureByCode');
		}
		else
		{
			throw new waException("некорректное значение [{$field}] параметра [\$field]. должно быть 'id' или 'code'");
		}

		$features = array();

		/** @var shopSeofilterFeature $feature */
		foreach (array_filter(array_map($get_feature_function, $values)) as $feature)
		{
			if ($group_key === null)
			{
				$features[] = $feature;
			}
			else
			{
				$features[$feature->$group_key] = $feature;
			}
		}

		return $features;
	}

	public static function hash($params)
	{
		if (count($params) == 0)
		{
			return '';
		}

		$params = self::normalizeParams(self::filterRangeParams($params));

		if (count($params) == 0)
		{
			return '';
		}

		return sha1(self::key($params));
	}

	public static function key($params)
	{
		$query = http_build_query($params, null, '&');

		return $query;
	}

	public static function normalizeParams($params)
	{
		// todo $settings->excluded_get_params

		$params = self::filterNotFeatureParams($params);

		foreach (array_keys($params) as $i)
		{
			if (strtolower($i) !== 'price' && strtolower($i) !== 'price_min' && strtolower($i) !== 'price_max')
			{
				if (!is_array($params[$i]))
				{
					$params[$i] = array($params[$i]);
				}

				if (!array_key_exists($i, self::$boolean_feature_codes))
				{
					$params[$i] = array_filter($params[$i]);
				}

				$is_only_unit = isset($params[$i]['unit']) && count($params[$i]) === 1;

				if ($is_only_unit || !count($params[$i]))
				{
					unset($params[$i]);
				}
			}
			elseif ($params[$i] === '')
			{
				unset($params[$i]);
			}
		}

		foreach ($params as $code => $ids)
		{
			if (is_array($ids))
			{
				$tmp_ids = $ids;
				$keys = array_keys($ids);
				wa_is_int(reset($keys))
					? sort($tmp_ids, SORT_NUMERIC)
					: ksort($tmp_ids, SORT_NUMERIC);
				$params[$code] = array_unique($tmp_ids);
			}
			else
			{
				$params[$code] = $ids;
			}
		}

		ksort($params);

		return $params;
	}

	/**
	 * @param shopSeofilterFilter[] $filters
	 * @param array() $filter_params
	 * @param string|null $currency
	 * @return null|shopSeofilterFilter
	 */
	public static function resolveHashCollision($filters, $filter_params, $currency = null)
	{
		$filter_params = self::normalizeParams($filter_params);

		foreach ($filters as $filter)
		{
			$params = $currency === null
				? $filter->getFeatureValuesAsFilterParams()
				: $filter->getFeatureValuesAsFilterParamsForCurrency($currency);

			if (shopSeofilterFilter::paramsAreEqual($params, $filter_params))
			{
				return $filter;
			}
		}
		unset($filter);

		return null;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public static function filterRangeParams($params)
	{
		$result = array();

		foreach ($params as $key => $values)
		{
			$is_array_range = !is_array($values) && ($key == 'price_min' || $key == 'price_max');
			$is_range = is_array($values) && (isset($values['min']) || isset($values['max']));
			if ($is_array_range || $is_range)
			{
				continue;
			}

			$result[$key] = $values;
		}

		return $result;
	}


	/**
	 * @param string|shopBooleanValue|shopColorValue|shopDimensionValue $feature_value
	 * @param string $feature_name
	 * @return string
	 */
	public static function getValueName($feature_value, $feature_name = null)
	{
		if (!$feature_value)
		{
			return '';
		}

		if ($feature_value instanceof shopColorValue)
		{
			return $feature_value->value;
		}

		if ($feature_value instanceof shopBooleanValue)
		{
			$bool_value = $feature_value->value == 1
				? 'да'
				: 'нет';

			if (!$feature_name)
			{
				$feature = self::getFeatureById($feature_value->feature_id);
				if ($feature)
				{
					$feature_name = $feature->name;
				}
			}

			return $feature_name
				? mb_strtolower($bool_value . ' ' . $feature_name)
				: $bool_value;
		}

		if ($feature_value instanceof shopDimensionValue)
		{
			return $feature_value->value . ' ' . $feature_value->unit_name;
		}

		return (string)$feature_value;
	}

	/**
	 * @param $category_id
	 * @param array $filter_params
	 * @param bool $in_stock_only
	 * @return array
	 */
	public static function fastGetFeatureValueIds($category_id, $filter_params, $in_stock_only)
	{
		$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);
		$collection->groupBy('p.id');
		if ($in_stock_only)
		{
			$collection->filters(array('in_stock_only' => true));
		}
		$alias = $collection->addJoin('shop_product_features');

		$params = array(
			'values_count' => 0
		);

		$collection_filters = array();
		$inner_where = array();

		$i = 1;
		foreach ($filter_params as $feature_id => $value_ids)
		{
			if (is_array($value_ids) && (isset($value_ids['min']) || isset($value_ids['max'])))
			{
				$feature = self::getFeatureById($feature_id);
				if ($feature)
				{
					$collection_filters[$feature->code] = $value_ids;
				}
			}
			else
			{
				$inner_where[] = '('.$alias.'.feature_id = :feature_id_'.$i.' AND '
					.$alias.'.feature_value_id ' . (!is_array($value_ids) || count($value_ids) == 1 ? ' = :value_ids_'.$i : ' IN (s:value_ids_'.$i.')') . ')';
				$params['feature_id_'.$i] = $feature_id;
				$params['value_ids_'.$i] = is_array($value_ids) && count($value_ids) == 1 ? reset($value_ids) : $value_ids;
				$params['values_count']++;

				$i++;
			}
		}

		if (count($inner_where))
		{
			$collection->addWhere('(' . implode(' OR ', $inner_where) . ')');
		}

		if (count($collection_filters))
		{
			$collection->filters($collection_filters);
		}

		$collection->addJoin('shop_product_skus', ':table.product_id = p.id', ':table.available = 1');


		$having_query = "\n\t" . 'HAVING COUNT(DISTINCT '.$alias.'.feature_id) = :values_count';
		if (count($inner_where))
		{
			$sql = '
SELECT DISTINCT pf.feature_id, pf.feature_value_id
FROM (
	SELECT p.id
	' . $collection->getSQL() . "\n\t" . 'GROUP BY p.id' . $having_query . '
) p
JOIN shop_product_features pf
ON p.id = pf.product_id';
		}
		else
		{
			$sql = '
SELECT DISTINCT pf.feature_id, pf.feature_value_id
FROM (
	SELECT DISTINCT p.id
	' . $collection->getSQL() . '
) p
JOIN shop_product_features pf
ON p.id = pf.product_id';
		}

		$model = new waModel();
		$rows = $model->query($sql, $params);

		$feature_value_ids = array();
		foreach ($rows as $row)
		{
			$feature_value_ids[$row['feature_id']][] = $row['feature_value_id'];
		}

		return $feature_value_ids;
	}

	public static function getCurrentSpecialGetParams()
	{
		self::filterNotFeatureParams(waRequest::get());

		return array_keys(self::$not_existing_codes);
	}

	public static function getGetParametersForSearch()
	{
		$params = waRequest::get();

		$codes = array_keys($params);

		if (!count($codes))
		{
			return array();
		}

		$result = array();

		foreach (self::getFeatures('code', $codes, 'code') as $code => $feature)
		{
			$feature_type = $feature->type;
			$param_feature_value = $params[$code];

			if ($feature_type === 'double' && is_array($param_feature_value))
			{
				try
				{
					$value_model = shopFeatureModel::getValuesModel($feature_type);
					if (!$value_model)
					{
						continue;
					}
				}
				catch (waException $e)
				{
					continue;
				}

				$value_ids = $value_model->getValueIdsByRange(
					$feature->id,
					ifset($param_feature_value['min']),
					ifset($param_feature_value['max'])
				);

				$result[$code] = count($value_ids) == 1
					? $value_ids
					: $param_feature_value;
			}
			else
			{
				$result[$code] = $params[$code];
			}
		}

		foreach (array('price_min', 'price_max') as $price_key)
		{
			if (array_key_exists($price_key, $params))
			{
				$result[$price_key] = $params[$price_key];
			}
		}

		return $result;
	}

	/**
	 * @return shopSeofilterFeature[]
	 * @throws waException
	 */
	public static function getPossibleFilterFeatures()
	{
		if (self::$possible_features === null)
		{
			$model = new shopFeatureModel();

			$query = $model
				->select('id')
				->where("(`selectable`=1 OR `type`='boolean' OR `type`='double' OR `type` LIKE 'dimension.%')")
				->where('`parent_id` IS NULL')
				->where("`type` NOT LIKE '2d.%'")
				->where("`type` NOT LIKE '3d.%'")
				->query();

			$ids = array();
			foreach ($query as $row)
			{
				$ids[] = $row['id'];
			}

			self::$possible_features = self::getFeatures('id', $ids, 'id');
		}

		return self::$possible_features;
	}

	public static function arrayMergeRecursive(array &$array1, array &$array2)
	{
		$merged = $array1;

		foreach ($array2 as $key => &$value)
		{
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
			{
				$merged[$key] = self::arrayMergeRecursive($merged[$key], $value);
			}
			else
			{
				$merged[$key] = $value;
			}
		}

		return $merged;
	}

	private static function filterNotFeatureParams($params)
	{
		$codes_to_find = array();
		$params_keys = array_keys($params);
		foreach ($params_keys as $code)
		{
			if (!isset(self::$existing_codes[$code]))
			{
				$codes_to_find[] = $code;
			}
		}

		if (count($codes_to_find))
		{
			$found_features = shopSeofilterFilterFeatureValuesHelper::getFeatures('code', $codes_to_find, 'code');

			foreach ($codes_to_find as $code)
			{
				if (array_key_exists($code, $found_features))
				{
					self::$existing_codes[$code] = true;

					if ($found_features[$code]->type == shopFeatureModel::TYPE_BOOLEAN)
					{
						self::$boolean_feature_codes[$code] = $code;
					}
				}
				else
				{
					self::$not_existing_codes[$code] = true;
				}
			}
		}

		foreach ($params_keys as $code)
		{
			if (isset(self::$not_existing_codes[$code]))
			{
				unset($params[$code]);
			}
		}

		return $params;
	}

	private static function loadAllFeatures()
	{
		if (self::$features_by_id === null)
		{
			$feature_model = new shopFeatureModel();

			foreach ($feature_model->select('*')->query() as $feature_params)
			{
				$feature = new shopSeofilterFeature($feature_params);

				self::$features_by_id[$feature->id] = $feature;
				self::$features_by_code[$feature->code] = $feature;
			}
		}
	}
}
