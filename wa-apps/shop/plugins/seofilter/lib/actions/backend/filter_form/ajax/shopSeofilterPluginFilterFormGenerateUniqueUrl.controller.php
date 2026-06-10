<?php

class shopSeofilterPluginFilterFormGenerateUniqueUrlController extends shopSeofilterBackendFilterFormJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);

		$filter_attributes = $state['seo_filter'];

		if ($filter_attributes === null)
		{
			$this->formError('Filter attributes are missing');

			return;
		}

		$filter = $this->prepareFilter($filter_attributes);
		$filter->setIsNewRecord(!($filter->id > 0));

		$this->response = array(
			'filter_unique_url' => shopSeofilterFilterUrl::generateUniqueUrl($filter),
		);
	}
}