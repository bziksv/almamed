<?php

class shopSeofilterPluginBackendEditAction extends shopSeofilterBackendFilterFormViewAction
{
	public function execute()
	{
		$this->setTemplate('FilterEdit');

		$filter_id = waRequest::get('id');
		$filter = new shopSeofilterFilter();

		$filter = $filter->getById($filter_id);

		if (!$filter)
		{
			throw new waException("Filter with id [{$filter_id}]not found", 404);
		}

		$this->prepareForm($filter);

		$this->view->assign('save_action', shopSeofilterPluginFilterFormActions::ACTION_SAVE);
	}
}