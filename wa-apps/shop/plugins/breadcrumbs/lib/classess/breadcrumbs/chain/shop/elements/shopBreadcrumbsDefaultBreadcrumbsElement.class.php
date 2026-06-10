<?php

class shopBreadcrumbsDefaultBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $name;
	private $url;

	public function __construct($name = null, $url = null)
	{
		$this->name = $name === null
			? wa()->getResponse()->getTitle()
			: $name;

		$this->url = $url === null
			? wa()->getRouting()->getCurrentUrl()
			: $url;
	}

	protected function initializeItems()
	{
		return array(
			array(
				'name' => $this->name,
				'url' => $this->url,
			)
		);
	}
}