<?php

class shopBreadcrumbsBlogRootBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $blog;
	private $element_html;

	public function __construct($blog, $element_html)
	{
		$this->blog = $blog;
		$this->element_html = $element_html;
	}

	protected function initializeItems()
	{
		if (!$this->blog)
		{
			return array();
		}

		return array(
			array(
				'name' => $this->element_html ? $this->element_html : $this->blog['name'],
				'url' => blogBlog::getUrl($this->blog),
				'itemprop_name' => $this->blog['name'],
			)
		);
	}
}