<?php

class shopSeofilterPagination
{
	const LIST_PAGINATION_SHOW_ADJ_PAGES = 2;
	const ITEM_PER_PAGE_DEFAULT = 30;
	const ITEM_PER_PAGE_MAX = 500;

	private $total_count;
	private $current_page;

	private $pagination_items;

	public function __construct($total_count, $current_page)
	{
		$this->total_count = $total_count;
		$this->current_page = $current_page;
		$this->pagination_items = null;
	}

	public static function itemsPerPage()
	{
		$storage = wa()->getStorage();

		$limit = $storage->get('shop_seofilter_list_limit');

		if ($limit === 0)
		{
			return self::ITEM_PER_PAGE_MAX;
		}

		return $limit === null ? self::ITEM_PER_PAGE_DEFAULT : $limit;
	}

	public static function setItemsPerPage($limit)
	{
		if ($limit === null)
		{
			return;
		}

		if ($limit === 0)
		{
			$limit = self::ITEM_PER_PAGE_MAX;
		}

		$storage = wa()->getStorage();

		$storage->set('shop_seofilter_list_limit', $limit);
	}

	public function generateTemplateItems()
	{
		if ($this->total_count > self::itemsPerPage())
		{
			$this->pagination_items = array();
			$pages_total = ceil($this->total_count / self::itemsPerPage());

			$max_range_length = self::LIST_PAGINATION_SHOW_ADJ_PAGES * 2 + 1;
			if ($pages_total <= $max_range_length)
			{
				$start_page_number = 1;
				$end_page_number = $pages_total;
			}
			else
			{
				$start_page_number = ($this->current_page - self::LIST_PAGINATION_SHOW_ADJ_PAGES) > 0
					? $this->current_page - self::LIST_PAGINATION_SHOW_ADJ_PAGES
					: 1;

				$start_page_number = ($this->current_page + self::LIST_PAGINATION_SHOW_ADJ_PAGES) <= $pages_total
					? $start_page_number
					: $pages_total - $max_range_length + 1;

				$end_page_number = max($this->current_page + self::LIST_PAGINATION_SHOW_ADJ_PAGES, $max_range_length);
				$end_page_number = $end_page_number > $pages_total ? $pages_total : $end_page_number;
			}

			$page_numbers = range($start_page_number, $end_page_number, 1);

			if ($this->current_page > 1)
			{
				$this->pushPaginationItem($this->current_page - 1, '←', true);
			}

			if ($start_page_number == 3)
			{
				$this->pushPaginationItem(1, '1', true);
				$this->pushPaginationItem(2, '2', true);
			}
			else if ($start_page_number > 3)
			{
				$this->pushPaginationItem(1, '1', true);
				$this->pushPaginationItem(null, '...', false);
			}
			else if ($start_page_number == 2)
			{
				$this->pushPaginationItem(1, '1', true);
			}

			foreach ($page_numbers as $number)
			{
				$this->pushPaginationItem($number, $number, true);
			}

			$tmp = array_slice($page_numbers, -1, 1);
			$last_page_index = array_shift($tmp);
			if ($last_page_index == $pages_total - 2)
			{
				$this->pushPaginationItem($pages_total - 1, $pages_total - 1, true);
				$this->pushPaginationItem($pages_total, $pages_total, true);
			}
			else if ($last_page_index < $pages_total - 2)
			{
				$this->pushPaginationItem(null, '...', false);
				$this->pushPaginationItem($pages_total, $pages_total, true);
			}
			else if ($last_page_index == $pages_total - 1)
			{
				$this->pushPaginationItem($pages_total, $pages_total, true);
			}

			if ($this->current_page < $pages_total)
			{
				$this->pushPaginationItem($this->current_page + 1, '→', true);
			}
		}

		return $this->pagination_items;
	}

	private function pushPaginationItem($page, $title, $is_page)
	{
		$this->pagination_items[] = array(
			'is_page' => $is_page,
			'page' => $page,
			'title' => '' . $title,
			'is_current' => $this->current_page == $page,
		);
	}
}