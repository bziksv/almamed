<?php

class shopBreadcrumbsBlogHomeBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $home_name;

	public function __construct($home_name)
	{
		$this->home_name = $home_name;
	}

	public function initializeItems()
	{
		return array(
			array(
				'name' => $this->home_name,
				'url' => wa('blog')->getAppUrl('blog'),
				'arrow' => null,
				'itemprop_name' => 'Блог',
			)
		);
	}
}