<?php

class shopSeofilterPluginBackendCloneAction extends shopSeofilterBackendFilterFormViewAction
{
	public function execute()
	{
		$this->setTemplate('FilterClone');

		$rule_id = waRequest::get('id');
		$filter = new shopSeofilterFilter();

		$filter = $filter->getById($rule_id);

		if (!$filter)
		{
			throw new waException("Filter with id [{$rule_id}]not found", 404);
		}

		$filter->seo_name .= ' (copy)';

		$this->prepareForm($filter);

		$this->view->assign('save_action', shopSeofilterPluginFilterFormActions::ACTION_CLONE);
	}
}