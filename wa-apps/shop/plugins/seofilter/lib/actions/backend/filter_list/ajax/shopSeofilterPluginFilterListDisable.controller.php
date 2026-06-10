<?php

class shopSeofilterPluginFilterListDisableController extends shopSeofilterBackendFiltersListJsonController
{
	public function execute()
	{
		$ids = waRequest::post('ids', array());

		$filterRecord = new shopSeofilterFilter();

		if ($filterRecord->disableById($ids))
		{
			$this->formSuccess();
		}
		else
		{
			$ids_str = is_array($ids) ? implode(', ', $ids) : $ids;
			$this->formError('Can\'t disable filters with ids [' . $ids_str . ']');
		}
	}
}