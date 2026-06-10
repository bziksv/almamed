<?php


class shopSeoWaLazyRouting
{
	public function getParamsByRouteAndPluginId($route, $plugin_id)
	{
		$url = $this->getRequestUrl($route);
		$params = $this->getParamsByPluginIdAndUrl($plugin_id, $url);
		
		return $params;
	}
	
	private function getRequestUrl($route)
	{
		$url = wa()->getConfig()->getRequestUrl(true, true);
		$url = urldecode($url);
		
		if ($route && isset($route['app']) && $route['app'] && wa()->appExists($route['app'])) {
			// dispatch app routes
			$params = waRequest::param();
			$u = $route['url'];
			
			if (preg_match_all('/<([a-z_]+):?([^>]*)?>/ui', $u, $match, PREG_OFFSET_CAPTURE|PREG_SET_ORDER)) {
				$offset = 0;
				foreach ($match as $m) {
					$v = $m[1][0];
					$s = (isset($params[$v]) && $v != 'url') ? $params[$v] : '';
					$u = substr($u, 0, $m[0][1] + $offset).$s.substr($u, $m[0][1] + $offset + strlen($m[0][0]));
					$offset += strlen($s) - strlen($m[0][0]);
				}
			}
			
			$root_url = waRouting::clearUrl($u);
			$url = isset($params['url']) ? $params['url'] : substr($url, strlen($root_url));
		}
		
		return $url;
	}
	
	private function getParamsByPluginIdAndUrl($plugin_id, $url)
	{
		$routing = wa('shop')->getRouting();
		$params = waRequest::param();
		$plugin_info = wa('shop')->getConfig()->getPluginInfo($plugin_id);
		
		try
		{
			$plugin = wa('shop')->getPlugin($plugin_id);
			$routing_method = ifset($plugin_info['handlers']['routing'], null);
			
			if ($plugin && $routing_method && method_exists($plugin, $routing_method))
			{
				$routes = $plugin->$routing_method();
				$routes = shopSeoWaRoutingExt::handleRoutes($routing, $plugin_id, $routes);
				$params = shopSeoWaRoutingExt::getParams($routing, $routes, $url);
			}
		}
		catch (waException $e)
		{
		}
		
		return $params;
	}
}