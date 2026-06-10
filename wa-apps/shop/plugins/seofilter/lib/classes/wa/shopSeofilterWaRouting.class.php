<?php


class shopSeofilterWaRouting extends waRouting
{
	public static function getParams(waRouting $routing, $routes, $url)
	{
		$src_params = waRequest::param();
		$routing->dispatchRoutes($routes, $url);
		$params = waRequest::param();
		waRequest::setParam($src_params);

		return $params;
	}

	public static function handleRoutes(waRouting $routing, $plugin, $routes)
	{
		foreach ($routes as $url => $route)
		{
			if (!is_array($route))
			{
				list($route_ar['module'], $route_ar['action']) = explode('/', $route);
				$route = $route_ar;
			}
			if (!array_key_exists('plugin', $route))
			{
				$route['plugin'] = $plugin;
			}

			$routes[$url] = $route;
		}

		$routes = $routing->formatRoutes($routes, true);

		return $routes;
	}
}