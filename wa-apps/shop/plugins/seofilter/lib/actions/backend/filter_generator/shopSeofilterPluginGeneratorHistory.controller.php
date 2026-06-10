<?php

class shopSeofilterPluginGeneratorHistoryController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$history_ar = new shopSeofilterGeneratorHistory();

		$history = array();
		/** @var shopSeofilterGeneratorHistory $history_item */
		foreach ($history_ar->getAll() as $history_item)
		{
			$attributes = $history_item->getAttributes();
			$features = $history_item->features;
			@usort($features, array($this, '_compareHistoryFeatures'));

			$attributes['checked'] = false;
			$attributes['features'] = implode(', ', array_map(array($this, '_getHistoryFeatureName'), $features));
			unset($attributes['id']);

			$history[] = $attributes;
		}

		$this->response = $history;
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