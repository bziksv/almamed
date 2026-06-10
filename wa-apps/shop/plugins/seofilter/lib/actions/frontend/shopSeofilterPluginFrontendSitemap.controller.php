<?php

class shopSeofilterPluginFrontendSitemapController extends waViewController
{
	public function execute()
	{
		$route = wa()->getRouting()->getRoute();
		$route['domain'] = wa()->getRouting()->getDomain();

		$page = waRequest::param('sitemap_page', null, waRequest::TYPE_INT);

		if ($page === 0)
		{
			throw new waException("Page not found", 404);
		}

		$sitemap_config = new shopSeofilterSitemapConfig($route, $page);
		$sitemap_config->display();
	}

	public function run($params = null)
	{
		$this->preExecute();
		$this->execute();
	}
}