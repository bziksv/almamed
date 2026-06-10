<?php

class shopBreadcrumbsBlogPostBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $post;

	public function __construct($post)
	{
		$this->post = $post;
	}

	protected function initializeItems()
	{
		$post = $this->post;
		if (!$post)
		{
			return array();
		}

		return array(
			array(
				'name' => $post['title'],
				'url' => blogPost::getUrl($post),
			)
		);
	}
}