<?php

class shopBreadcrumbsShopPageBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $shop_page;
	private $page_model;

	public function __construct($shop_page)
	{
		$this->shop_page = $shop_page;
		$this->page_model = new shopPageModel();
	}

	protected function initializeItems()
	{
		$page = $this->shop_page;

		if (!$page)
		{
			return array();
		}

		$pages = array();

		do
		{
			$seo_extender = new shopBreadcrumbsSeoExtender();
			$page_extended = $seo_extender->extendShopPage($page);

			array_unshift($pages, array(
				'name' => $page_extended['name'],
				'url' => wa()->getAppUrl('shop', true) . $page_extended['full_url'],
			));

			$page = $this->getParent($page);
		}
		while ($page);

		return $pages;
	}

	private function getParent($page)
	{
		if (!is_array($page) || !ifset($page['parent_id']))
		{
			return null;
		}

		return $this->page_model->getById($page['parent_id']);
	}
}