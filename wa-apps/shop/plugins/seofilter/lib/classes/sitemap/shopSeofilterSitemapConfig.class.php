<?php

class shopSeofilterSitemapConfig extends waSitemapConfig
{
	private $route;
	private $url_type;
	private $page;

	public function __construct($route, $page = null)
	{
		parent::__construct();

		$this->route = $route;
		$this->url_type = ifset($route['url_type'], 0);
		$this->page = $page;
	}

	public function execute()
	{
		if ($this->page === 0)
		{
			throw new waException("Page not found", 404);
		}

		if ($this->page === null)
		{
			$this->displayRootSitemap();
			return;
		}

		$this->displaySitemap();
	}

	public function count()
	{
		$sitemap = $this->getSitemap();

		return $sitemap->countPages();
	}

	public function display()
	{
		$system = waSystem::getInstance();
		$system->getResponse()->addHeader('Content-Type', 'application/xml; charset=UTF-8');
		$system->getResponse()->sendHeaders();


		if ($this->page === null)
		{
			echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="' . $system->getUrl(true) . 'wa-content/xml/sitemap-index.xsl"?>
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';
			$this->execute();
			echo '</sitemapindex>';
		}
		else
		{
			echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="' . $system->getUrl(true) . 'wa-content/xml/sitemap.xsl"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';
			if ($this->domain)
			{
				$this->execute();
			}
			echo '</urlset>';
		}
	}

	private function displayRootSitemap()
	{
		$count = $this->count();

		if ($count == 0)
		{
			return;
		}

		for ($page = 1; $page <= $count; $page++)
		{
			$route_params = array(
				'module' => 'frontend',
				'plugin' => 'seofilter',
				'action' => 'sitemap',
				'sitemap_page' => $page
			);
			echo '<sitemap>
	<loc>' . wa()->getRouteUrl('shop', $route_params, true) . '</loc>
	<lastmod>' . date('c') . '</lastmod>
</sitemap>
';
		}
	}

	private function displaySitemap()
	{
		$sitemap = $this->getSitemap();

		foreach ($sitemap->generate($this->page) as $url)
		{
			$this->addUrl(
				$url['loc'],
				$url['lastmod'],
				$url['changefreq'],
				$url['priority']
			);
		}
	}

	/**
	 * @return shopSeofilterSitemapCachedSitemap
	 */
	private function getSitemap()
	{
		$config = waSystem::getInstance('shop')->getConfig();

		if (isset($this->route['currency']) && strlen($this->route['currency']))
		{
			$currency = $this->route['currency'];
		}
		else
		{
			$currency = $config instanceof shopConfig
				? $config->getCurrency()
				: waRequest::param('currency');
		}

		$settings = shopSeofilterBasicSettingsModel::getSettings();

		return new shopSeofilterSitemapCachedSitemap($this->route, $currency, $settings->consider_category_filters);
	}
}