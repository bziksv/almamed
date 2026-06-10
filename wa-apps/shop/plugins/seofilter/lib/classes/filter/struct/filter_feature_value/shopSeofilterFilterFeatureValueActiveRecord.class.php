<?php

/**
 * @property int $feature_id
 * @property int $sort
 * @property shopSeofilterFeature|null $feature
 * @property string $feature_type
 * @property string $feature_code
 * @property string $feature_name
 * @property shopFeatureValuesModel|null $feature_values_model
 */
abstract class shopSeofilterFilterFeatureValueActiveRecord extends shopSeofilterActiveRecord
{
	abstract function getValueName();

	protected function getFeature()
	{
		return shopSeofilterFilterFeatureValuesHelper::getFeatureById($this->feature_id);
	}

	protected function getFeature_type()
	{
		return $this->getFeatureFieldValue('type');
	}

	protected function getFeature_code()
	{
		return $this->getFeatureFieldValue('code');
	}

	protected function getFeature_name()
	{
		return $this->getFeatureFieldValue('name');
	}

	protected function getFeature_values_model()
	{
		$type = $this->getFeature_type();

		if ($type)
		{
			try
			{
				return shopFeatureModel::getValuesModel($type);
			}
			catch (Exception $e)
			{
			}
		}

		return null;
	}

	private function getFeatureFieldValue($field)
	{
		$feature = $this->getFeature();

		return $feature
			? $feature->$field
			: null;
	}
}