<?php

class shopSeofilterPluginFilterListDeleteController extends shopSeofilterBackendFiltersListJsonController
{
	public function execute()
	{
		$ids = waRequest::post('ids', array());

		$filterRecord = new shopSeofilterFilter();
		$fields = array(
			$filterRecord->primaryKeyField() => $ids
		);
		$filters = $filterRecord->getAllByFields($fields);

		$success = true;
		foreach ($filters as $filter)
		{
			$success = $filter->delete() && $success;
		}

		if ($success)
		{
			$this->formSuccess();
		}
		else
		{
			$ids_str = is_array($ids) ? implode(', ', $ids) : $ids;
			$this->formError('Can\'t delete filters with ids [' . $ids_str . ']');
		}
	}
}