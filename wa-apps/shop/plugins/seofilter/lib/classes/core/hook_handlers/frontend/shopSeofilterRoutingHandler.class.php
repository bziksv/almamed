<?php

class shopSeofilterRoutingHandler extends shopSeofilterHookHandler
{
	private static $smarty_with_productbrands_hook = array();

	private $routing = array();

	protected function handle()
	{
		$this->routing = array();

		$this->core();
		$this->sitemap();
		$this->productbrandsPlugin();

		return $this->routing;
	}

	protected function beforeHandle()
	{
		return wa()->getEnv() != 'backend' && parent::beforeHandle();
	}

	protected function defaultHandleResult()
	{
		return array();
	}

	private function sitemap()
	{
		if (!$this->settings->use_sitemap_hook)
		{
			$this->routing['filter-sitemap.xml'] = 'frontend/sitemap';
			$this->routing['filter-sitemap-<sitemap_page>.xml'] = 'frontend/sitemap';
		}
	}

	private function core()
	{
		$plugin_routing = shopSeofilterRouting::instance();
		$plugin_routing->setRoute($this->params);
		$current_url = wa()->getRouting()->getCurrentUrl();

		unset($_GET['_']);
		unset($_REQUEST['_']);

		if ($plugin_routing->isInitialized() || !strlen($current_url))
		{
			return;
		}

		$plugin_routing->initializePluginRouting($current_url);

		if ($plugin_routing->isSeofilterPage())
		{
			$plugin_routing->tryToPerformRedirects();

			$plugin_routing->triggerEvent();

			$plugin_routing->removeSeofilterSuffixFromUrl();
			$plugin_routing->patchGetParameters();

			$plugin_routing->redispatch();
		}
		elseif ($plugin_routing->isCategoryPage())
		{
			$plugin_routing->tryRedirectToFilterPage();
		}
	}

	private function productbrandsPlugin()
	{
		if (!shopSeofilterHelper::isProductbrandsPluginInstalled() || !$this->isProductbrandsPlugin())
		{
			return;
		}

		$smarty = wa()->getView()->smarty;
		$smarty_hash = spl_object_hash($smarty);

		if (array_key_exists($smarty_hash, self::$smarty_with_productbrands_hook))
		{
			return;
		}

		try
		{
			$handler = new shopSeofilterSmartyProductbrandsHandler();

			$smarty->registerPlugin(
				'function',
				'shop_seofilter_productbrands_hook',
				array($handler, 'handle')
			);

			self::$smarty_with_productbrands_hook[$smarty_hash] = true;
		}
		catch (SmartyException $ignored)
		{
		}
	}

	private function isProductbrandsPlugin()
	{
		$params = waRequest::param();

		if (!isset($params['module']))
		{
			$url = $this->getRequestUrl($this->params);
			$params = $this->getParamsByPlugin('productbrands', $url);
		}

		return ifset($params['app']) == 'shop'
			&& ifset($params['module']) == 'frontend'
			&& ifset($params['plugin']) == 'productbrands';
	}

	private function getRequestUrl($route)
	{
		$url = wa()->getConfig()->getRequestUrl(true, true);
		$url = urldecode($url);

		if ($route && isset($route['app']) && $route['app'] && wa()->appExists($route['app']))
		{
			// dispatch app routes
			$params = waRequest::param();
			$u = $route['url'];

			if (preg_match_all('/<([a-z_]+):?([^>]*)?>/ui', $u, $match, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
			{
				$offset = 0;
				foreach ($match as $m)
				{
					$v = $m[1][0];
					$s = (isset($params[$v]) && $v != 'url') ? $params[$v] : '';
					$u = substr($u, 0, $m[0][1] + $offset) . $s . substr($u, $m[0][1] + $offset + strlen($m[0][0]));
					$offset += strlen($s) - strlen($m[0][0]);
				}
			}

			$root_url = waRouting::clearUrl($u);
			$url = isset($params['url']) ? $params['url'] : substr($url, strlen($root_url));
		}

		return $url;
	}

	private function getParamsByPlugin($plugin_id, $url)
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
				$routes = shopSeofilterWaRouting::handleRoutes($routing, $plugin_id, $routes);
				$params = shopSeofilterWaRouting::getParams($routing, $routes, $url);
			}
		}
		catch (waException $e)
		{
		}

		return $params;
	}
}
