<?php

class shopSeofilterProductFiltersCollection
{
	/** @var shopProduct */
	private $product;
	private $storefront;
	private $category_ids;
	private $filter_feature_values_count;

	public function __construct($product)
	{
		if ($product instanceof shopProduct)
		{
			$this->product = $product;
		}
		elseif (is_array($product) && array_key_exists('id', $product) && $product['id'] > 0)
		{
			$this->product = new shopProduct($product['id']);
		}
		elseif (wa_is_int($product) && $product > 0)
		{
			$this->product = new shopProduct($product);
		}
		else
		{
			$this->product = null;
		}
	}

	/**
	 * @return shopSeofilterFilter[][]
	 */
	public function getCategoryFilters()
	{
		$product = $this->product;
		if ($product === null)
		{
			return array();
		}

		$category_filter_ids = $this->getProductPossibleCategoryFilterIds();

		$filter_ids = array();
		foreach ($category_filter_ids as $_filter_ids)
		{
			foreach ($_filter_ids as $id)
			{
				$filter_ids[$id] = $id;
			}
		}


		$features = $product->features;

		/** @var shopSeofilterFilter[] $filters */
		$filters = array();

		$ar = new shopSeofilterFilter();
		$filter_search_params = array(
			'id' => $filter_ids,
			'feature_value_ranges_count' => 0,
			'is_enabled' => '1',
		);

		if ($this->filter_feature_values_count > 0)
		{
			$filter_search_params['feature_values_count'] = $this->filter_feature_values_count;
		}

		$all_filters = $ar->getAllByFields($filter_search_params);

		foreach ($all_filters as $filter)
		{
			if (count($filter->featureValueRanges) > 0)
			{
				continue;
			}

			if ($this->filter_feature_values_count > 0 && count($filter->featureValues) != $this->filter_feature_values_count)
			{
				continue;
			}

			foreach ($filter->featureValues as $filter_value)
			{
				$code = $filter_value->feature_code;
				if (!array_key_exists($code, $features))
				{
					continue 2;
				}

				$product_value = $features[$code];
				$product_has_filter_value = false;

				if (is_array($product_value))
				{
					foreach ($product_value as $product_value_id => $_)
					{
						if ($filter_value->value_id == $product_value_id)
						{
							$product_has_filter_value = true;
							break;
						}
					}
				}
				else
				{
					if (is_string($product_value))
					{
						if ($filter_value->value_value == $product_value)
						{
							$product_has_filter_value = true;
						}
					}
					elseif ($product_value instanceof shopBooleanValue)
					{
						if ($filter_value->value_id == $product_value->value)
						{
							$product_has_filter_value = true;
						}
					}
					else
					{
						if ($filter_value->value_id == $product_value->id)
						{
							$product_has_filter_value = true;
						}
					}
				}

				if (!$product_has_filter_value)
				{
					continue 2;
				}
			}

			$filters[$filter->id] = $filter;
		}
		unset($filter);

		$category_filters = array();
		foreach ($category_filter_ids as $category_id => $_filter_ids)
		{
			$category_filters[$category_id] = array();
			foreach ($_filter_ids as $filter_id)
			{
				if (array_key_exists($filter_id, $filters))
				{
					$category_filters[$category_id][$filters[$filter_id]->id] = $filters[$filter_id];
				}
			}
		}

		return $category_filters;
	}

	/**
	 * @param string $storefront
	 * @return shopSeofilterProductFiltersCollection
	 */
	public function setStorefront($storefront)
	{
		$this->storefront = $storefront;

		return $this;
	}

	/**
	 * @param int[] $category_ids
	 * @return shopSeofilterProductFiltersCollection
	 */
	public function setCategories($category_ids)
	{
		foreach ($category_ids as $category_id)
		{
			$this->category_ids[$category_id] = $category_id;
		}

		return $this;
	}

	/**
	 * @param int $filter_feature_values_count
	 * @return shopSeofilterProductFiltersCollection
	 */
	public function filterFilterFeatureValuesCount($filter_feature_values_count)
	{
		$this->filter_feature_values_count = $filter_feature_values_count;

		return $this;
	}

	private function getProductCategoryIds()
	{
		$model = new shopCategoryProductsModel();
		$sql = "SELECT c.id FROM shop_category_products cp JOIN shop_category c ON cp.category_id = c.id
        WHERE cp.product_id = i:id ORDER BY c.left_key";

		$data = $model->query($sql, array('id' => $this->product->id))->fetchAll('id');

		if (is_array($this->category_ids))
		{
			$ids = array();

			foreach ($this->category_ids as $category_id)
			{
				if (array_key_exists($category_id, $data))
				{
					$ids[$category_id] = $category_id;
				}
			}

			$ids_to_check = array_keys($ids);
		}
		else
		{
			$ids_to_check = array_keys($data);
		}

		$ids = array();
		foreach ($ids_to_check as $id)
		{
			$ids[$id] = $id;
		}

		$parents_sql = '
SELECT c2.id
FROM shop_category c1, shop_category c2
WHERE c1.id IN (s:ids)
	AND c2.`status` = \'1\'
	AND c2.include_sub_categories = \'1\'
	AND c2.id = c1.parent_id
';

		while (count($ids_to_check))
		{
			$query_params = array('ids' => $ids_to_check);
			$ids_to_check = array();

			foreach ($model->query($parents_sql, $query_params) as $row)
			{
				$id = $row['id'];

				$ids_to_check[] = $id;

				$ids[$id] = $id;
			}
		}

		return $ids;
	}

	private function getProductPossibleCategoryFilterIds()
	{
		$cache_model = new shopSeofilterSitemapCacheModel();
		$category_ids = $this->getProductCategoryIds();

		$storefronts = array();
		if ($this->storefront)
		{
			$storefronts[] = $this->storefront;
		}

		$search = array(
			'category_id' => $category_ids,
			'storefront' => $storefronts,
		);

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if ($settings->cache_for_single_storefront)
		{
			unset($search['storefront']);
		}

		$rows = $cache_model->getByField($search, true);

		$category_filter_ids = array();
		foreach ($rows as $row)
		{
			if ($row['filter_ids'] != '')
			{
				$category_id = $row['category_id'];

				if (!array_key_exists($category_id, $category_filter_ids))
				{
					$category_filter_ids[$category_id] = array();
				}

				foreach (explode(',', $row['filter_ids']) as $filter_id)
				{
					$category_filter_ids[$category_id][$filter_id] = $filter_id;
				}
			}
		}

		return $category_filter_ids;
	}
}
