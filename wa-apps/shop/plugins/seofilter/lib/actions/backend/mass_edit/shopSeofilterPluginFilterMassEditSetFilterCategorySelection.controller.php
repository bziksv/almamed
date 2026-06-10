<?php

class shopSeofilterPluginFilterMassEditSetFilterCategorySelectionController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		try
		{
			list($filter_ids, $category_ids, $category_use_mode) = $this->tryGetParams();
		}
		catch (waException $e)
		{
			return;
		}

		$collection = $this->getFiltersCollection($filter_ids);
		foreach ($collection as $filter)
		{
			$filter->categories_use_mode = $category_use_mode;
			$filter->filter_categories = $category_use_mode == shopSeofilterFilter::USE_MODE_ALL
				? array()
				: $category_ids;

			$filter->save();
		}

		$this->response['success'] = true;
	}

	/**
	 * @throws waException
	 */
	private function tryGetParams()
	{
		$state_json = waRequest::post('state_json');
		$state = json_decode($state_json, true);
		if (!is_array($state))
		{
			throw new waException();
		}

		$filter_ids = $state['filter_ids'];
		$category_ids = $state['selected_category_ids'];
		$category_use_mode = $state['category_use_mode'];

		if (!is_array($filter_ids) || count($filter_ids) === 0)
		{
			throw new waException();
		}

		return array($filter_ids, $category_ids, $category_use_mode);
	}

	/**
	 * @param array $filter_ids
	 * @return shopSeofilterFilterCollection
	 */
	private function getFiltersCollection($filter_ids)
	{
		$params = array(
			'ids' => $filter_ids,
		);

		$sql = '
SELECT SQL_CALC_FOUND_ROWS *
FROM shop_seofilter_filter
WHERE id IN (:ids)
';

		return new shopSeofilterFilterCollection($sql, $params);
	}
}
