<?php

class shopSearchproProductFeaturesHelper
{
	protected $shop_product_features_model;

	protected function getShopProductFeaturesModel()
	{
		if(!isset($this->shop_product_features_model)) {
			$this->shop_product_features_model = new shopProductFeaturesModel();
		}

		return $this->shop_product_features_model;
	}

	public function getFeaturesValues($product_id, $is_public_only = true, $is_selectable_features = false, $disabled_features = array())
	{
		if(empty($product_id)) {
			return array();
		}

		$sql = <<<SQL
SELECT DISTINCT pf.feature_id, pf.feature_value_id FROM shop_product_features AS pf
SQL;

		if($is_public_only) {
			$sql .= " LEFT JOIN shop_feature AS f ON f.id = pf.feature_id";
		}

		$sql .= " WHERE pf.product_id IN (?)";

		if($is_public_only) {
			$sql .= " AND f.status = 'public'";
		}

		if(!$is_selectable_features) {
			$sql .= " AND pf.sku_id IS NULL";
		}

		if(!empty($disabled_features)) {
			$sql .= " AND pf.feature_id NOT IN (?)";
		}

		$results = $this->getShopProductFeaturesModel()->query($sql, array($product_id, $disabled_features))->fetchAll('feature_id', 2);

		return $results;
	}
}
