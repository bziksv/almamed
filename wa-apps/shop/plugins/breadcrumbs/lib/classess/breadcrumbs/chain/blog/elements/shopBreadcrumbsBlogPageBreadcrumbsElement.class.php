<?php

class shopBreadcrumbsBlogPageBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $blog_page;
	private $page_model;

	public function __construct($blog_page)
	{
		$this->blog_page = $blog_page;
		$this->page_model = new shopPageModel();
	}

	protected function initializeItems()
	{
		$page = $this->blog_page;

		if (!$page)
		{
			return array();
		}

		$pages = array();

		do
		{
			array_unshift($pages, array(
				'name' => $page['name'],
				'url' => '/' . trim($page['route'], '/*') . '/' . $page['full_url'],
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