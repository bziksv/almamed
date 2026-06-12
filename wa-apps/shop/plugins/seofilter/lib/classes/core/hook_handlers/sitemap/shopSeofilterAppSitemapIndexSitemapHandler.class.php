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
		if (empty($route['domain'])) {
			$route['domain'] = $this->domain ?: wa()->getRouting()->getDomain(null, true);
		}

		$sitemap_config = new shopSeofilterSitemapConfig($route);
		if ($sitemap_config->count() === 0) {
			return array();
		}

		return array(array(
			'url' => wa('shop')->getRouting()->getUrl('shop', array(
				'module' => 'frontend',
				'plugin' => 'seofilter',
				'action' => 'sitemap',
			), true, $route['domain'], $route['url']),
			'lastmod' => date('c'),
		));
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