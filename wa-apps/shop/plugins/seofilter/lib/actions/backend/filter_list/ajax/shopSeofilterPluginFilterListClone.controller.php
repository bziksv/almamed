<?php

class shopSeofilterPluginFilterListCloneController extends shopSeofilterBackendFiltersListJsonController
{
	public function execute()
	{
		$ids = waRequest::post('ids', array());

		$names_next_index = array();
		$filterRecord = new shopSeofilterFilter();

		$success = true;
		foreach ($filterRecord->getAllByFields(array('id' => $ids)) as $filter)
		{
			$clean_name = $filter->seo_name;
			if (preg_match('/(^.+?)\s+\((\d+)\)$/', $clean_name, $matches))
			{
				$clean_name = $matches[1];
			}
			if (!isset($names_next_index[$clean_name]))
			{
				$names_next_index[$clean_name] = $filter->model()->maxCloneIndex($clean_name) + 1;
			}
			$clone_name = $clean_name . ' (' . $names_next_index[$clean_name]++ . ')';

			$clone_attributes = array(
				'is_enabled' => shopSeofilterFilter::ENABLED,
				'seo_name' => $clone_name,
			);

			if ($filter->cloneRecord($clone_attributes) === null)
			{
				$success = false;
			}
		}

		if ($success)
		{
			$this->formSuccess();
		}
		else
		{
			$ids_str = is_array($ids) ? implode(', ', $ids) : $ids;
			$this->formError('Can\'t clone filters with ids [' . $ids_str . ']');
		}
	}
}