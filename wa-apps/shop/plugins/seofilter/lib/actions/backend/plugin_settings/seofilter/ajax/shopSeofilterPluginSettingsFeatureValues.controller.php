<?php

class shopSeofilterPluginSettingsFeatureValuesController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$feature_model = new shopFeatureModel();

		$feature = $feature_model->getById(waRequest::post('feature_id'));

		$features = array($feature);
		$features = $feature_model->getValues($features);
		$feature = $features[0];

		foreach ($feature['values'] as $i => $value)
		{
			$feature['values'][$i] = array(
				'name' => shopSeofilterFilterFeatureValuesHelper::getValueName($value, $feature['name']),
			);
		}

		$this->response = $feature['values'];
	}
}