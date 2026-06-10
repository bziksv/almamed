<?php

class shopSeofilterSitemapHandler extends shopSeofilterHookHandler
{
	public function handle()
	{
		$route = $this->params;

		$route['domain'] = wa()->getRouting()->getDomain();

		$currency = $route['currency'];

		$sitemap = new shopSeofilterSitemapCachedSitemap($route, $currency, $this->settings->consider_category_filters);

		$urls = $sitemap->generate(shopSeofilterISitemap::ALL_URLS);

		return $urls;
	}

	protected function beforeHandle()
	{
		return $this->settings->is_enabled && $this->settings->use_sitemap_hook;
	}

	protected function defaultHandleResult()
	{
		return array();
	}
}