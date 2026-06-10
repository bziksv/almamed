<?php

class shopSeofilterAppSitemapIndexSitemapHandler extends shopSeofilterHookHandler
{
	private $domain;
	private $route;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->domain = isset($params['domain']) ? $params['domain'] : null;
		$this->route = $params['route'];
	}

	protected function handle()
	{
		$route = $this->route;

		$sitemap_config = new shopSeofilterSitemapConfig($this->route);
		$pages_count = $sitemap_config->count();

		$url_params = array();

		for ($page = 1; $page <= $pages_count; $page++)
		{
			$route_params = array(
				'module' => 'frontend',
				'plugin' => 'seofilter',
				'action' => 'sitemap',
				'sitemap_page' => $page
			);

			$url_params[] = array(
				'url' => wa('shop')->getRouting()->getUrl('shop', $route_params, true, $this->domain, $route['url']),
				'lastmod' => date('c'),
			);
		}

		return $url_params;
	}

	protected function beforeHandle()
	{
		return $this->settings->is_enabled && !$this->settings->use_sitemap_hook;
	}

	protected function defaultHandleResult()
	{
		return array();
	}
}