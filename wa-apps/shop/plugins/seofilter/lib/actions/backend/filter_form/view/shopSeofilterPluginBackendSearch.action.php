<?php

class shopSeofilterPluginBackendSearchAction extends shopSeofilterBackendViewAction
{
	public function execute()
	{
		$this->setTemplate('FilterSearch');

		$filter_params = waRequest::get();
		unset($filter_params['plugin']);
		unset($filter_params['action']);
		unset($filter_params['module']);
		unset($filter_params['app']);

		$filter_ar = new shopSeofilterFilter();
		$filter = $filter_ar->getByFeatureValues($filter_params);

		if ($filter)
		{
			$this->redirect('/webasyst/shop/?plugin=seofilter&action=edit&id=' . $filter->id);
		}
	}
}