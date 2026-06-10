<?php

/**
 * @property int $id
 * @property int $history_id
 * @property int $feature_id
 * @property int $order
 *
 * relations
 * @property shopSeofilterFeature|null $feature
 * @property shopSeofilterGeneratorHistory $history
 */
class shopSeofilterGeneratorHistoryFeature extends shopSeofilterActiveRecord
{
	public function relations()
	{
		return array(
			'history' => array(self::BELONGS_TO, 'shopSeofilterGeneratorHistory', 'history_id'),
		);
	}

	public function getFeature()
	{
		if (!$this->feature_id)
		{
			return null;
		}

		return shopSeofilterFilterFeatureValuesHelper::getFeatureById($this->feature_id);
	}
}