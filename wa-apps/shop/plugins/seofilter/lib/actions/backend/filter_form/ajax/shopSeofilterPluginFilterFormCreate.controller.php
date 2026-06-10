<?php

class shopSeofilterPluginFilterFormCreateController extends shopSeofilterBackendFilterFormJsonController
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

		$filter = $this->prepareFilter($filter_attributes);
		$filter->id = null;

		$this->prepareRelatedObjects($filter, $features_values, $personal_rules_attributes, $personal_canonicals);
		$this->saveFilter($filter);
		$this->saveFilterFieldValues($filter, $field_values);

		$this->response = array(
			'save_success' => !$this->validate_only,
			'redirect_url' => $filter->id ? '?plugin=seofilter&action=edit&id=' . $filter->id : '',
			'feature_value_id_map' => $this->save_feature_value_id_map,
		);
	}
}