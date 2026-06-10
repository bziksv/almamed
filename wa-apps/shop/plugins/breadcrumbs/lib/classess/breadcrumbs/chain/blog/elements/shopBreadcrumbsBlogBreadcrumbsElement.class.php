<?php

class shopBreadcrumbsBlogBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $blog;

	public function __construct($blog)
	{
		$this->blog = $blog;
	}

	protected function initializeItems()
	{
		if (!$this->blog)
		{
			return array();
		}

		return array(
			array(
				'name' => $this->blog['name'],
				'url' => blogBlog::getUrl($this->blog),
			)
		);
	}
}