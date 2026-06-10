<?php

class shopSeoWaRouting implements shopSeoStorefrontSource
{
	const GENERAL_STOREFRONT = '*';

	public function isPageAction()
	{
		return $this->isAction('frontend', 'page');
	}

	public function getStorefronts()
	{
		$routing = wa()->getRouting();
		$domains = $routing->getByApp('shop');
		$urls = array();

		foreach ($domains as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if ((!method_exists($routing, 'isAlias') || !$routing->isAlias($domain)) and isset($route['url']))
				{
					$urls[] = $domain . '/' . $route['url'];
				}
			}
		}

		return $urls;
	}

	public function getCurrentStorefront()
	{
		$routing = wa()->getRouting();
		$route = $routing->getRoute();

		if ($route['app'] === 'shop')
		{
			$domain = $routing->getDomain();

			return $domain . '/' . $route['url'];
		}

		return null;
	}

	private function isAction($module, $action, $plugin = null)
	{
		return waRequest::param('module') === $module
			&& waRequest::param('action') === $action
			&& waRequest::param('plugin') === $plugin;
	}
}
