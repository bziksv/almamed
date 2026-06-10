<?php

class shopSearchproFeaturesHelper
{
	protected $shop_feature_model;

	protected function getShopFeatureModel()
	{
		if(!isset($this->shop_feature_model)) {
			$this->shop_feature_model = new shopFeatureModel();
		}

		return $this->shop_feature_model;
	}

	public function getFeaturesCanFilter($key = null)
	{
		$features = $this->getShopFeatureModel()->select('*')->where("status = 'public'")->order('name ASC')->fetchAll($key);

		if(!$features) {
			return array();
		}

		return $features;
	}

	public function takeFeatureIds($feature_values)
	{
		$feature_ids = array();
		foreach($feature_values as $feature) {
			$feature_ids[$feature['feature_id']] = true;
		}

		return array_keys($feature_ids);
	}

	public function getTypeFeatures($type_id)
	{
		$type_features_model = new shopTypeFeaturesModel();
		$rows = $type_features_model
			->select('type_id, feature_id')
			->where('type_id IN (i:type_id)', array('type_id' => $type_id))
			->order('sort')
			->fetchAll();

		$type_features = array();
		foreach($rows as $row) {
			$type_features[$row['type_id']][] = $row['feature_id'];
		}

		return $type_features;
	}

	public function getFeatures($feature_ids, $is_values = false)
	{
		$sql = "SELECT * FROM `shop_feature` WHERE id IN (i:ids) OR type = 'divider'";

		if($is_values) {
			$feature_ids = array_unique(array_map(wa_lambda('$a', 'return $a["feature_id"];'), $feature_ids)); // todo in 5.6 use array_column
		}

		return $this->getShopFeatureModel()->query($sql, array('ids' => $feature_ids))->fetchAll('id');
	}

	public function getFeatureType($feature)
	{
		return preg_replace('/\..*$/', '', $feature['type']);
	}

	public function fill($rows, $features, &$references, $is_public_only = true)
	{
		foreach($rows as $row) {
			if(empty($features[$row['feature_id']])) {
				continue;
			}

			$f = $features[$row['feature_id']];

			if($is_public_only && $f['status'] != 'public') {
				unset($features[$row['feature_id']]);
				continue;
			}

			if(array_key_exists('type_values', $references)) {
				$type = $this->getFeatureType($f);
				if($type != shopFeatureModel::TYPE_BOOLEAN && $type != shopFeatureModel::TYPE_DIVIDER) {
					$references['type_values'][$type][$row['feature_value_id']] = $row['feature_value_id'];
				}
			}

			if(array_key_exists('product_features', $references)) {
				if($f['multiple']) {
					$references['product_features'][$row['product_id']][$f['id']][$row['feature_value_id']] = $row['feature_value_id'];
				} else {
					$references['product_features'][$row['product_id']][$f['id']] = $row['feature_value_id'];
				}
			}
		}

		if(array_key_exists('type_values', $references)) {
			foreach($references['type_values'] as $type => $value_ids) {
				$model = shopFeatureModel::getValuesModel($type);

				if($model) {
					$references['type_values'][$type] = $model->getValues('id', $value_ids);
				}
			}
		}
	}
}
