<?php

/**
 * Хранилище фильтров
 */
class shopSeofilterFiltersStorage implements shopSeofilterIFiltersStorage
{
	/** @var shopSeofilterFilter[] */
	private static $filters = array();

	/**
	 * @param $filter_id
	 * @return shopSeofilterFilter|null
	 */
	public function getById($filter_id)
	{
		if (!array_key_exists($filter_id, self::$filters))
		{
			$ar = new shopSeofilterFilter();

			self::$filters[$filter_id] = $ar->getById($filter_id);
		}

		return self::$filters[$filter_id];
	}

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @return shopSeofilterFilter[]
	 * @deprecated used in update script only
	 */
	public function getAllForCategory($storefront, $category_id)
	{
		$query = $this->buildFiltersQuery($storefront, $category_id);

		$filters = array();
		foreach ($query as $row)
		{
			$filter_id = $row['id'];

			if (!isset(self::$filters[$filter_id]))
			{
				self::$filters[$filter_id] = new shopSeofilterFilter($row);
			}

			$filters[$filter_id] = self::$filters[$filter_id];
		}

		return $filters;
	}

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @param int $feature_values_count
	 * @return shopSeofilterFilter[]
	 */
	public function getFiltersWithNFeatureValuesForCategory($storefront, $category_id, $feature_values_count)
	{
		$query = $this->getFiltersWithNFeatureValuesQuery($storefront, $category_id, $feature_values_count);

		$filters = array();
		foreach ($query as $row)
		{
			$filter_id = $row['id'];

			if (!isset(self::$filters[$filter_id]))
			{
				self::$filters[$filter_id] = new shopSeofilterFilter($row);
			}

			$filters[$filter_id] = self::$filters[$filter_id];
		}

		return $filters;
	}

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @return int[]
	 */
	public function getAllFilterIdsForCategory($storefront, $category_id)
	{
		$query = $this->buildFiltersQuery($storefront, $category_id);

		$ids = array();

		foreach ($query as $row)
		{
			$ids[$row['id']] = (int)$row['id'];
		}

		return $ids;
	}

	public function getAllFilterIdsWithSingleValue()
	{
		$key = 'plugins/seofilter/filter_ids_with_single_value';

		$cache = new waSerializeCache($key, 600, 'shop');

		if ($cache->isCached())
		{
			return $cache->get();
		}

		$sql = '
SELECT fv.filter_id
FROM shop_seofilter_filter_feature_value AS fv
	LEFT JOIN shop_seofilter_filter_feature_value_range AS fvr
		ON fvr.filter_id = fv.filter_id
WHERE fvr.id IS NULL
GROUP BY fv.filter_id
HAVING COUNT(fv.filter_id) = 1
';

		$model = new waModel();

		$all_single_value_filter_ids = array();
		foreach ($model->query($sql) as $row)
		{
			$all_single_value_filter_ids[$row['filter_id']] = $row['filter_id'];
		}

		$cache->set($all_single_value_filter_ids);

		return $all_single_value_filter_ids;
	}

	/**
	 * Возвращает фильтр по ID витрины, ID категории и URL фильтра
	 *
	 * @param string $storefront
	 * @param int $category_id
	 * @param string $filter_url
	 * @return shopSeofilterFilter
	 */
	public function getByUrl($storefront, $category_id, $filter_url)
	{
		$attributes = $this->buildFiltersQuery($storefront, $category_id, $filter_url)->fetchAssoc();
		if (!$attributes)
		{
			return null;
		}

		$filter_id = $attributes['id'];
		if (!isset(self::$filters[$filter_id]))
		{
			self::$filters[$filter_id] = new shopSeofilterFilter($attributes);
		}

		return self::$filters[$filter_id];
	}

	public function getByFilterParams($storefront, $category_id, $filter_params, $currency)
	{
		if (count($filter_params) == 0)
		{
			return null;
		}

		$hash = shopSeofilterFilterFeatureValuesHelper::hash($filter_params);

		$query = $this->buildFiltersQuery($storefront, $category_id, null, $hash);

		$filters = array();
		foreach ($query as $row)
		{
			$filter_id = $row['id'];

			if (!isset(self::$filters[$filter_id]))
			{
				self::$filters[$filter_id] = new shopSeofilterFilter($row);
			}

			if (!self::$filters[$filter_id]->hasDeletedFeatureValues())
			{
				$filters[] = self::$filters[$filter_id];
			}
		}

		if (count($filters) == 0)
		{
			return null;
		}

		return shopSeofilterFilterFeatureValuesHelper::resolveHashCollision($filters, $filter_params, $currency);
	}

	/**
	 * @param string $seo_name
	 * @param string $url
	 * @param array $categories
	 * @param array $storefronts
	 * @param string $feature_values_count
	 * @param array $features
	 * @param string $show_corrupted_filters
	 * @param string $sort
	 * @param string $order
	 * @param int $offset
	 * @param int $limit
	 * @return shopSeofilterFilterCollection
	 */
	public function backendList(
		$seo_name = '',
		$url = '',
		$categories = array(),
		$storefronts = array(),
		$feature_values_count = '',
		$features = array(),
		$show_corrupted_filters = '0',
		$sort = shopSeofilterFilter::DEFAULT_SORT,
		$order = shopSeofilterFilter::DEFAULT_ORDER,
		$offset = 0,
		$limit = 0
	)
	{
		$filter = new shopSeofilterFilter();

		$filter_feature_value = new shopSeofilterFilterFeatureValue();
		$filter_feature_value_range = new shopSeofilterFilterFeatureValueRange();

		$filter_category = new shopSeofilterFilterCategory();
		$filter_storefront = new shopSeofilterFilterStorefront();

		$model = new waModel();
		$sort = $model->escape($sort);
		$order = $model->escape($order);

		$params = array();
		$join = array();
		$where = array();

		if (strlen(trim($seo_name)) != 0)
		{
			$where[] = 'f.seo_name LIKE \'' . $seo_name . '\'';
		}

		if (strlen(trim($url)) != 0)
		{
			$where[] = 'f.url LIKE \'' . $url . '\'';
		}

		if (count($categories) != 0)
		{
			$join['category'] = 'JOIN ' . $filter_category->tableName() . ' f_c ON f_c.filter_id = f.id';
			$where[] = 'f_c.category_id IN (:categories)';
			$params['categories'] = implode(',', $categories);
		}

		if (count($storefronts) != 0)
		{
			$escaped = array();
			foreach ($storefronts as $storefront)
			{
				$escaped[] = '\'' . $model->escape($storefront) . '\'';
			}

			$join['storefront'] = 'JOIN ' . $filter_storefront->tableName() . ' f_s ON f_s.filter_id = f.id';
			$where[] = 'f_s.storefront IN (' . implode(',', $escaped) . ')';
		}

		if ($feature_values_count != 0)
		{
			$where[] = '(f.feature_values_count + f.feature_value_ranges_count) = :feature_values_count';
			$params['feature_values_count'] = $feature_values_count;
		}

		if (count($features) != 0)
		{
			$join['feature_value'] = 'LEFT JOIN ' . $filter_feature_value->tableName() . ' f_fv ON f_fv.filter_id = f.id';
			$join['feature_value_range'] = 'LEFT JOIN ' . $filter_feature_value_range->tableName() . ' f_fvr ON f_fvr.filter_id = f.id';

			$where[] = '(f_fv.feature_id IN (:feature_ids) OR f_fvr.feature_id IN (:feature_ids))';
			$params['feature_ids'] = implode(',', $features);
		}

		if ($show_corrupted_filters == '1')
		{
			$checker = new shopSeofilterFilterFeatureValueChecker();

			$params['corrupted_filter_ids'] = $checker->getInvalidFilterIds();
			$where[] = count($params['corrupted_filter_ids']) > 0
				? 'f.id IN (:corrupted_filter_ids)'
				: '1 = 0';
		}



		if ($sort === 'storefront')
		{
			$join['storefront'] = 'JOIN ' . $filter_storefront->tableName() . ' f_s ON f_s.filter_id = f.id';
			$sort = 'f_s.storefront';
		}
		else
		{
			$sort = 'f.' . $sort;
		}

		$sql = '
SELECT SQL_CALC_FOUND_ROWS `f`.*
FROM `' . $filter->tableName() . '` `f`';

		if (count($join))
		{
			$sql .= PHP_EOL . implode(PHP_EOL, $join);
		}

		if (count($where))
		{
			$sql .= PHP_EOL . 'WHERE ' . implode(' AND ', $where);
		}

		$sql .= PHP_EOL . 'GROUP BY `f`.`id`';
		$sql .= PHP_EOL . 'ORDER BY ' . $sort . ' ' . strtoupper($order);

		return new shopSeofilterFilterCollection($sql, $params, $offset, $limit);
	}

	public function getFiltersWithNFeatureValuesQuery($storefront, $category_id, $n)
	{
		return $this->buildFiltersQuery($storefront, $category_id, null, null, $n);
	}

	/**
	 * @param int $filter_id
	 * @param string $storefront
	 * @param int $category_id
	 * @return shopSeofilterFilterPersonalCanonical|null
	 */
	public function getFilterCanonical($filter_id, $storefront, $category_id)
	{
		$params = array(
			'mode_all' => shopSeofilterFilterPersonalCanonical::USE_MODE_ALL,
			'mode_listed' => shopSeofilterFilterPersonalCanonical::USE_MODE_LISTED,
			'mode_except' => shopSeofilterFilterPersonalCanonical::USE_MODE_EXCEPT,
			'enabled' => shopSeofilterFilterPersonalCanonical::ENABLED,
			'storefront' => $storefront,
			'category_id' => $category_id,
			'filter_id' => $filter_id,
		);

		$canonical = new shopSeofilterFilterPersonalCanonical();
		$canonical_storefront = new shopSeofilterFilterPersonalCanonicalStorefront();
		$canonical_category = new shopSeofilterFilterPersonalCanonicalCategory();

		$canonical_table = '`' . $canonical->tableName() . '`';
		$storefront_table = '`' . $canonical_storefront->tableName() . '`';
		$category_table = '`' . $canonical_category->tableName() . '`';

		$sql = '
SELECT r.*
FROM ' . $canonical_table . ' r
LEFT JOIN ' . $storefront_table . ' s ON s.canonical_id = r.id
LEFT JOIN ' . $category_table . ' c ON c.canonical_id = r.id
WHERE 
(
	r.storefronts_use_mode = :mode_all
	OR (r.storefronts_use_mode = :mode_listed AND s.storefront = :storefront)
	OR (r.storefronts_use_mode = :mode_except AND r.id NOT IN (
		SELECT DISTINCT r.id
		FROM ' . $canonical_table . ' r
		JOIN ' . $storefront_table . ' s ON s.canonical_id = r.id
		WHERE r.storefronts_use_mode = :mode_except AND s.storefront = :storefront
		AND r.is_enabled = :enabled
	))
)
AND
(
	r.categories_use_mode = :mode_all
	OR (r.categories_use_mode = :mode_listed AND c.category_id = :category_id)
	OR (r.categories_use_mode = :mode_except AND r.id NOT IN (
		SELECT DISTINCT r.id
		FROM ' . $canonical_table . ' r
		JOIN ' . $category_table . ' c ON c.canonical_id = r.id
		WHERE r.categories_use_mode = :mode_except AND c.category_id = :category_id
		AND r.is_enabled = :enabled
	))
)
AND r.is_enabled = :enabled AND r.filter_id = :filter_id
GROUP BY r.id
ORDER BY r.storefronts_use_mode DESC, r.categories_use_mode DESC
';

		$model = new waModel();
		$canonicals_raw = $model->query($sql, $params)->fetchAll();

		if (!$canonicals_raw)
		{
			return null;
		}

		$canonical_raw = array_shift($canonicals_raw);
		foreach ($canonical_raw as $meta => $template)
		{
			if (mb_strlen(trim($template)))
			{
				continue;
			}

			foreach ($canonicals_raw as $personal_rule)
			{
				if (mb_strlen(trim($personal_rule[$meta])))
				{
					$canonical_raw[$meta] = $personal_rule[$meta];
					break;
				}
			}
		}

		return new shopSeofilterFilterPersonalCanonical($canonical_raw);
	}

	private function buildFiltersQuery($storefront, $category_id, $url = null, $hash = null, $feature_values_count = null)
	{
		$params = array(
			'mode_all' => shopSeofilterFilter::USE_MODE_ALL,
			'mode_listed' => shopSeofilterFilter::USE_MODE_LISTED,
			'mode_except' => shopSeofilterFilter::USE_MODE_EXCEPT,
			'enabled' => shopSeofilterFilter::ENABLED,
			'storefront' => $storefront,
			'category_id' => $category_id,
		);

		$filter = new shopSeofilterFilter();
		$filter_storefront = new shopSeofilterFilterStorefront();
		$filter_category = new shopSeofilterFilterCategory();

		$filter_table = '`' . $filter->tableName() . '`';
		$storefront_table = '`' . $filter_storefront->tableName() . '`';
		$category_table = '`' . $filter_category->tableName() . '`';

		$where = array('f.is_enabled = :enabled');

		if ($url !== null)
		{
			$where[] = 'f.url = LOWER(:url)';
			$params['url'] = $url;
		}
		if ($hash !== null)
		{
			$where[] = 'f.feature_value_hash = :hash';
			$params['hash'] = $hash;
		}
		if ($feature_values_count !== null)
		{
			$where[] = 'f.feature_values_count = :feature_values_count';
			$where[] = 'f.feature_value_ranges_count = 0';
			$params['feature_values_count'] = $feature_values_count;
		}

		$sql = '
SELECT f.*
FROM ' . $filter_table . ' f
LEFT JOIN ' . $storefront_table . ' s ON s.filter_id = f.id
LEFT JOIN ' . $category_table . ' c ON c.filter_id = f.id
WHERE 
(
	f.storefronts_use_mode = :mode_all
	OR (f.storefronts_use_mode = :mode_listed AND s.storefront = :storefront)
	OR (f.storefronts_use_mode = :mode_except AND f.id NOT IN (
		SELECT DISTINCT f.id
		FROM ' . $filter_table . ' f
		JOIN ' . $storefront_table . ' s ON s.filter_id = f.id
		WHERE f.storefronts_use_mode = :mode_except AND s.storefront = :storefront
		AND f.is_enabled = :enabled
	))
)
AND
(
	f.categories_use_mode = :mode_all
	OR (f.categories_use_mode = :mode_listed AND c.category_id = :category_id)
	OR (f.categories_use_mode = :mode_except AND f.id NOT IN (
		SELECT DISTINCT f.id
		FROM ' . $filter_table . ' f
		JOIN ' . $category_table . ' c ON c.filter_id = f.id
		WHERE f.categories_use_mode = :mode_except AND c.category_id = :category_id
		AND f.is_enabled = :enabled
	))
)
AND ' . implode(' AND ', $where) . '
GROUP BY f.id
';

		$model = new waModel();
		return $model->query($sql, $params);
	}

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @param array $filter_params
	 * @param string $currency
	 * @return shopSeofilterFilter
	 * @deprecated старое название метода getByFilterParams
	 */
	public function getByFilter($storefront, $category_id, $filter_params, $currency)
	{
		return $this->getByFilterParams($storefront, $category_id, $filter_params, $currency);
	}
}
