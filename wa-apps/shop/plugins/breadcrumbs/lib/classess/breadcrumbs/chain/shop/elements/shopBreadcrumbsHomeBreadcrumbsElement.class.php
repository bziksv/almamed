<?php

class shopBreadcrumbsHomeBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $home_name;

	public function __construct($home_name)
	{
		$this->home_name = $home_name;
	}

	public function initializeItems()
	{
		if (waSystem::getApp() != 'shop')
		{
			$url = '/';
			foreach (wa()->getRouting()->getRoutes() as $route)
			{
				if (array_key_exists('app', $route) && $route['app'] == 'shop')
				{
					$url = rtrim('/' . ltrim($route['url'], '/*'), '/') . '/';
				}
			}
		}
		else
		{
			$url = wa()->getAppUrl();
		}

		return array(
			array(
				'name' => $this->home_name,
				'url' => $url,
				'arrow' => null,
				'itemprop_name' => 'Главная',
			)
		);
	}
}