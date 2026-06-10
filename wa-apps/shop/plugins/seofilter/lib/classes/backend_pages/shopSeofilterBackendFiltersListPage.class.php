<?php

class shopSeofilterBackendFiltersListPage
{
	private $sort;
	private $order;

	private $page;
	private $per_page;
	private $filter;
	private $filter_partial;

	public function __construct()
	{
		$this->init();
	}

	public function getFiltersList()
	{
		try
		{
			$storage = new shopSeofilterFiltersStorage();
			$collection = $storage->backendList(
				isset($this->filter_partial['seo_name']) ? $this->filter_partial['seo_name'] : '',
				isset($this->filter_partial['url']) ? $this->filter_partial['url'] : '',
				isset($this->filter['categories']) ? $this->filter['categories'] : array(),
				isset($this->filter['storefront']) ? $this->filter['storefront'] : array(),
				isset($this->filter['feature_values_count']) ? $this->filter['feature_values_count'] : '',
				isset($this->filter['features']) ? $this->filter['features'] : array(),
				isset($this->filter['show_corrupted_filters']) ? $this->filter['show_corrupted_filters'] : '0',
				$this->sort,
				$this->order,
				($this->page - 1) * $this->per_page,
				$this->per_page
			);
		}
		catch (Exception $e)
		{
			$err_log_timestamp = time();
			waLog::dump(array(
				'time' => $err_log_timestamp,
				'exception_message' => $e->getMessage(),
				'shopSeofilterBackendFilterPersonalRuleList' => $this,
			), 'seofilter_errors.log');

			return array(
				'filters' => array(),
				'pagination' => null,
				'total_count' => 0,
				'has_errors' => true,
			);
		}

		$filters_list = array();
		$iterator = $collection->iterator();
		foreach ($iterator as $filter)
		{
			$filters_list[] = array(
				'id' => $filter->id,
				'seo_name' => $filter->seo_name,
				'storefronts' => $filter->filter_storefronts,
				'categories' => $filter->filter_category_names,
				'url' => $filter->url,
				'is_enabled' => $filter->is_enabled == shopSeofilterFilter::ENABLED ? true : false,
				'checked' => false,
				'feature_values_count' => count($filter->featureValues) + count($filter->featureValueRanges),
				'rules_count' => count($filter->personalRules),
				'update_datetime' => date('H:i d.m.Y', strtotime($filter->update_datetime)),
				'exclude_storefronts' => $filter->storefronts_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT,
				'exclude_categories' => $filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT,
			);
		}

		$total_count = $collection->count();
		$pagination = $this->per_page > 0
			? new shopSeofilterPagination($total_count, $this->page)
			: null;

		return array(
			'filters' => $filters_list,
			'pagination' => $pagination ? $pagination->generateTemplateItems() : null,
			'total_count' => $total_count,
			'has_errors' => false,
		);
	}

	public function getSort()
	{
		return $this->sort;
	}

	public function getOrder()
	{
		return $this->order;
	}

	public function getFilter()
	{
		return $this->filter;
	}

	public function getFilterPartial()
	{
		return $this->filter_partial;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function getPerPage()
	{
		return $this->per_page;
	}

	private function init()
	{
		$this->initSortParameters();
		$this->initFilterParameters();
		$this->initPageParameters();
	}

	private function initSortParameters()
	{
		$storage = wa()->getStorage();

		$sort = waRequest::request('sort');
		$order = waRequest::request('order');

		$stored_sort = $storage->get('shop_seofilter_filter_sort');
		$stored_order = $storage->get('shop_seofilter_filter_order');

		if ($sort === null)
		{
			$sort = $stored_sort === null
				? shopSeofilterFilter::DEFAULT_SORT
				: $stored_sort;
		}

		if ($order === null)
		{
			$order = $stored_order === null
				? shopSeofilterFilter::DEFAULT_ORDER
				: $stored_order;
		}

		$storage->set('shop_seofilter_filter_sort', $sort);
		$storage->set('shop_seofilter_filter_order', $order);

		$this->sort = $sort;
		$this->order = $order;
	}

	private function initFilterParameters()
	{
		$storage = wa()->getStorage();

		$session_filter = $storage->get('shop_seofilter_backend_filter');
		$session_filter_partial = $storage->get('shop_seofilter_backend_filter_partial');

		$filter = waRequest::request('filter', array());
		$filter_partial = waRequest::request('filter_partial', array());

		$last_filtered_field = waRequest::request('last_filtered_field');
		if ($last_filtered_field)
		{
			switch ($last_filtered_field)
			{
				case 'seo_name':
				case 'url':
					if (!isset($filter_partial[$last_filtered_field]))
					{
						unset($session_filter_partial[$last_filtered_field]);
					}

					break;
				default:

					if (!isset($filter[$last_filtered_field]))
					{
						unset($session_filter[$last_filtered_field]);
					}
			}
		}


		if (!count($filter) && is_array($session_filter))
		{
			$filter = $session_filter;
		}
		if (!count($filter_partial) && is_array($session_filter_partial))
		{
			$filter_partial = $session_filter_partial;
		}
		$storage->set('shop_seofilter_backend_filter', $filter);
		$storage->set('shop_seofilter_backend_filter_partial', $filter_partial);

		$model = new waModel();

		foreach ($filter_partial as $name => $value)
		{
			if (!is_array($value))
			{
				$filter_partial[$name] = '%' . $model->escape($value) . '%';
			}
		}

		foreach ($filter as $name => $value)
		{
			if (!is_array($value))
			{
				$filter[$name] = $model->escape($value);
			}
		}

		$this->filter = $filter;
		$this->filter_partial = $filter_partial;
	}

	private function initPageParameters()
	{
		$this->page = waRequest::request('page', 1, waRequest::TYPE_INT);

		shopSeofilterPagination::setItemsPerPage(waRequest::request('per_page'));
		$this->per_page = shopSeofilterPagination::itemsPerPage();

		shopSeofilterPagination::setItemsPerPage($this->per_page);
	}
}
