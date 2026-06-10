<?php

class shopSeofilterPluginFilterMassEditRemoveCategoriesFromFiltersController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		try
		{
			list($filter_ids, $category_ids) = $this->tryGetParams();
		}
		catch (waException $e)
		{
			return;
		}

		$skipped_filters = array();

		$collection = $this->getFiltersCollection($filter_ids);
		foreach ($collection as $filter)
		{
			if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_ALL)
			{
				$filter->categories_use_mode = shopSeofilterFilter::USE_MODE_EXCEPT;
				$filter->filter_categories = $category_ids;
			}
			elseif ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_LISTED)
			{
				$filter_categories = array_fill_keys($filter->filter_categories, 1);
				foreach ($category_ids as $id)
				{
					unset($filter_categories[$id]);
				}

				if (count($filter_categories) === 0)
				{
					$skipped_filters[] = $this->getFilterParams($filter);

					continue;
				}

				$filter->filter_categories = array_keys($filter_categories);
			}
			elseif ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT)
			{
				$filter->filter_categories = array_unique(array_merge(
					$filter->filter_categories,
					$category_ids
				));
			}

			$filter->save();
		}

		$this->response['success'] = true;
		$this->response['skipped_filters'] = $skipped_filters;
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

		if (!is_array($filter_ids) || count($filter_ids) === 0)
		{
			throw new waException();
		}

		return array($filter_ids, $category_ids);
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

	private function getFilterParams(shopSeofilterFilter $filter)
	{
		return array(
			'id' => $filter->id,
			'seo_name' => $filter->seo_name,
			'url' => $filter->url,
		);
	}
}
