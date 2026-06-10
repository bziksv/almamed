<?php

class shopSeofilterFilterFeatureValuesGroup
{
	/** @var shopSeofilterFilterFeatureValue[] */
	private $filter_feature_values = array();
	private $value_names = array();

	public function addFeatureValue($feature_id, $value_id, $value_name)
	{
		$this->filter_feature_values[] = new shopSeofilterFilterFeatureValue(array(
			'feature_id' => $feature_id,
			'value_id' => $value_id,
			'sort' => count($this->filter_feature_values) + 1,
		));

		$this->value_names[] = $value_name;
	}

	public function getGroup()
	{
		return $this->filter_feature_values;
	}

	public function getName()
	{
		return implode(' ', $this->value_names);
	}
}