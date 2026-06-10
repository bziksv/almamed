<?php

/**
 * @property int $id
 * @property string $generator_id
 * @property int $total
 * @property int $created
 * @property int $skipped
 * @property int $date
 *
 * relations
 * @property shopSeofilterGeneratorHistoryFeature[] $features
 */
class shopSeofilterGeneratorHistory extends shopSeofilterActiveRecord
{
	public function relations()
	{
		return array(
			'features' => array(self::HAS_MANY, 'shopSeofilterGeneratorHistoryFeature', 'history_id'),
		);
	}

	/**
	 * @param string $generator_id
	 * @return shopSeofilterGeneratorHistory
	 */
	public function getByGeneratorId($generator_id)
	{
		$model = $this->model();

		$attributes = $model->getByField(array(
			'generator_id' => $generator_id,
		));

		if (!$attributes)
		{
			return null;
		}

		$obj = new shopSeofilterGeneratorHistory($attributes);
		$obj->setIsNewRecord(false);

		return $obj;
	}

	public function haveFilters()
	{
		$model = new shopSeofilterFilterModel();
		return $model->countByField('generator_process_id', $this->generator_id) > 0;
	}

	public function getViewAttributes()
	{
		$view_attributes = $this->getAttributes();
		$features = $this->features;
		@usort($features, array($this, '_compareHistoryFeatures'));

		$view_attributes['checked'] = false;
		$view_attributes['features'] = implode(', ', array_map(array($this, '_getHistoryFeatureName'), $features));
		$view_attributes['date'] = date('H:i d.m.Y', strtotime($this->date));
		unset($view_attributes['id']);

		return $view_attributes;
	}

	public function getAllNotEmpty()
	{
		$model = $this->model();
		$filter_ar = new shopSeofilterFilter();

		$objects = array();

		$sql = '
SELECT `history`.*
FROM `' . $this->tableName() . '` `history`
JOIN `' . $filter_ar->tableName() . '` `filter` ON `filter`.generator_process_id = `history`.generator_id
GROUP BY `filter`.generator_process_id
';
		foreach ($model->query($sql) as $attributes)
		{
			/** @var shopSeofilterActiveRecord $object */
			$object = new $this;
			$object->setAttributes($attributes);
			$object->setIsNewRecord(false);

			$objects[] = $object;
		}

		return $objects;
	}

	/**
	 * @param shopSeofilterGeneratorHistoryFeature $f1
	 * @param shopSeofilterGeneratorHistoryFeature $f2
	 * @return int
	 */
	private function _compareHistoryFeatures($f1, $f2)
	{
		if ($f1->order == $f2->order)
		{
			return 0;
		}

		return $f1->order < $f2->order
			? -1
			: 1;
	}

	/**
	 * @param shopSeofilterGeneratorHistoryFeature $feature
	 * @return string
	 */
	private function _getHistoryFeatureName($feature)
	{
		$_feature = $feature->feature;

		return $_feature ? $_feature->name : '';
	}
}