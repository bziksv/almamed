<?php

/**
 * Class shopSeofilterFilterFeatureValue
 * @property int $id
 * @property int $filter_id
 * @property int $feature_id
 * @property int $value_id
 * @property int $sort
 *
 * @property $value_value
 *
 * @method shopSeofilterFilterFeatureValueModel model()
 * @method shopSeofilterFilterFeatureValue|null getById($id)
 *
 * relations
 * @property shopSeofilterFeature|null $feature
 * @property array|null $featureValue
 * @property shopSeofilterFilter $filter
 */
class shopSeofilterFilterFeatureValue extends shopSeofilterFilterFeatureValueActiveRecord
{
	const ERROR_KEY_PREFIX = 'feature_value';

	protected static $_feature_values = array();

	private $_feature_value_object = false;
	private $_feature_value = false;

	public function relations()
	{
		return array(
			'filter' => array(self::BELONGS_TO, 'shopSeofilterFilter', 'filter_id'),
		);
	}

	public function getFeatureValue()
	{
		if ($this->_feature_value === false)
		{
			$feature = $this->feature;
			if (!$feature)
			{
				$this->_feature_value = null;

				return null;
			}

			$model = shopFeatureModel::getValuesModel($feature->type);

			$this->_feature_value = !$model || $feature->type === shopFeatureModel::TYPE_BOOLEAN
				? array(
					'id' => $this->value_id,
					'feature_id' => $feature->id,
					'sort' => 0,
					'value' => $this->value_id == 1 ? 'Да' : 'Нет',
				)
				: $model->getById($this->value_id);
		}

		return $this->_feature_value;
	}

	public function refreshRelations()
	{
		$this->_feature_value = false;
	}

	public function canBeRange()
	{
		$type = $this->feature->type;

		return $type === shopFeatureModel::TYPE_DOUBLE
			|| $type === shopFeatureModel::TYPE_DIMENSION
			|| $type === shopFeatureModel::TYPE_RANGE;
	}

	public function key()
	{
		//return self::ERROR_KEY_PREFIX . '_' . $this->feature_id . '_' . $this->value_id;
		return $this->feature_id . '/' . $this->value_id;
	}

	public function getValueName()
	{
		$value = $this->getFeatureValueObject();
		$feature = $this->feature;

		return shopSeofilterFilterFeatureValuesHelper::getValueName($value, $feature ? $feature->name : null);
	}

	protected function getFeatureValueObject()
	{
		if ($this->_feature_value_object === false)
		{
			if (!isset(self::$_feature_values[$this->feature_id]))
			{
				$feature = $this->getFeature();
				self::$_feature_values[$this->feature_id] = $feature
					? shopFeatureModel::getFeatureValues($feature->assoc())
					: null;
			}

			$this->_feature_value_object = isset(self::$_feature_values[$this->feature_id][$this->value_id])
				? self::$_feature_values[$this->feature_id][$this->value_id]
				: null;
		}

		return $this->_feature_value_object;
	}

	protected function getValue_value()
	{
		$model = $this->feature_values_model;

		if (!$model || $model instanceof shopFeatureValuesDividerModel)
		{
			return null;
		}

		if ($model instanceof shopFeatureValuesBooleanModel)
		{
			return $this->value_id;
		}
		else
		{
			$row = $model->getById($this->value_id);

			return $row ? $row['value'] : null;
		}
	}
}