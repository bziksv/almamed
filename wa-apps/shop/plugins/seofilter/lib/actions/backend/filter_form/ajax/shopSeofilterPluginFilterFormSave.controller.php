<?php

class shopSeofilterPluginFilterFormSaveController extends shopSeofilterBackendFilterFormJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);

		$filter_attributes = $state['seo_filter'];
		$features_values = $state['features_values'];
		$personal_rules_attributes = $state['personal_rules'];
		$field_values = $state['field_values'];
		$personal_canonicals = $state['personal_canonicals'];

		if ($filter_attributes === null)
		{
			$this->formError('Filter attributes are missing');

			return;
		}

		$userlog = shopUserlogPlugin::getInstance();
		$filter_id = (int) ifset($filter_attributes, 'id', 0);
		$filter_before = $userlog && shopUserlogSeofilterSnapshot::isAvailable()
			? shopUserlogSeofilterSnapshot::captureFilter($filter_id)
			: null;

		$filter = $this->prepareFilter($filter_attributes);
		$filter->setIsNewRecord(false);

		$this->prepareRelatedObjects($filter, $features_values, $personal_rules_attributes, $personal_canonicals);
		$this->saveFilter($filter);
		$this->saveFilterFieldValues($filter, $field_values);

		$this->response = array(
			'save_success' => !$this->validate_only,
			'redirect_url' => '',
			'feature_value_id_map' => $this->save_feature_value_id_map,
		);

		if ($userlog && $filter_before !== null && !$this->validate_only && empty($this->errors)) {
			$filter_after = shopUserlogSeofilterSnapshot::captureFilter($filter->id);
			$name = ifset($filter_after, 'seo_name', ifset($filter_before, 'seo_name', '#'.$filter->id));
			$userlog->logSettingsChange(
				'SEO-фильтр «'.$name.'»',
				$filter_before,
				$filter_after
			);
		}
	}
}