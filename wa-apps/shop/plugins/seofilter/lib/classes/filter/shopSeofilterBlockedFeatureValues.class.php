<?php

class shopSeofilterBlockedFeatureValues
{
	private $view_filters;
	private $filter_params;
	private $category;

	private $product_feature_model;

	private $available_feature_value_ids = null;

	public function __construct($view_filters, $filter_params, $category)
	{
		$this->view_filters = $view_filters;
		$this->filter_params = $filter_params;
		$this->category = $category;

		$this->product_feature_model = new shopProductFeaturesModel();
	}

	public function getAvailableFeatureValueIds()
	{
		if ($this->available_feature_value_ids === null)
		{
			$this->collectAvailableFeatureValueIds();
		}

		return $this->available_feature_value_ids;
	}

	private function collectAvailableFeatureValueIds()
	{
		$this->available_feature_value_ids = array();

		foreach ($this->view_filters as $filter_key => &$view_filter)
		{
			if (
				$filter_key == 'price' || $filter_key == 'sf_available'
				|| !array_key_exists('code', $view_filter)
				|| !array_key_exists('values', $view_filter) || !is_array($view_filter['values'])
			)
			{
				continue;
			}

			$possible_ids = $this->getPossibleFeatureValues($view_filter['code']);

			if ($possible_ids)
			{
				$this->available_feature_value_ids[$view_filter['code']] = array_map('strval', array_keys($possible_ids));
			}
		}
	}

	private function getPossibleFeatureValues($feature_code)
	{
		$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureByCode($feature_code);

		if (!$feature)
		{
			return false;
		}

		$filter_params = $this->filter_params;

		unset($filter_params[$feature->code]);

		$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $this->category['id']);
		$collection->filters($filter_params);

		$product_ids = array();


		$sql = 'SELECT p.id' . PHP_EOL . $collection->getSQL() . PHP_EOL . 'GROUP BY p.id';
		foreach ($this->product_feature_model->query($sql) as $product)
		{
			$product_ids[$product['id']] = $product['id'];
		}

		if (count($product_ids) == 0)
		{
			return array();
		}

		$sql = '
SELECT feature_value_id
FROM shop_product_features
WHERE product_id IN (:product_ids)
	AND feature_id = :feature_id
GROUP BY feature_value_id
';

		$query_params = array(
			'product_ids' => $product_ids,
			'feature_id' => $feature['id'],
		);

		$ids = array();
		foreach ($this->product_feature_model->query($sql, $query_params) as $row)
		{
			$id = $row['feature_value_id'];
			$ids[$id] = $id;
		}

		return $ids;
	}
}
