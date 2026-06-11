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
		// URL фильтров — в отдельных filter-sitemap-*.xml (см. AppSitemapIndexSitemapHandler).
		return false;
	}

	protected function defaultHandleResult()
	{
		return array();
	}
}