<?php

class shopBreadcrumbsCustomBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $breadcrumbs;

	public function __construct($breadcrumbs)
	{
		$this->breadcrumbs = $breadcrumbs;
	}

	protected function initializeItems()
	{
		$result = array();

		foreach ($this->breadcrumbs as $item)
		{
			if (is_array($item) && isset($item['name']) && isset($item['url']))
			{
				$result[] = $item;
			}
		}

		return $result;
	}
}