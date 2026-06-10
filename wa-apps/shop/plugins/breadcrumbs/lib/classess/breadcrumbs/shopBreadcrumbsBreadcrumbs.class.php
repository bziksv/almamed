<?php

class shopBreadcrumbsBreadcrumbs
{


	/** @var shopBreadcrumbsSettings|shopBreadcrumbsBlogSettings */
	private $settings;

	/** @var shopBreadcrumbsBreadcrumbsChain */
	private $breadcrumbs_chain;

	private $home_name;

	/**
	 * shopBreadcrumbsBreadcrumbs constructor.
	 * @param shopBreadcrumbsSettings|shopBreadcrumbsBlogSettings $settings
	 * @param string $home_name
	 */
	public function __construct($settings, $home_name)
	{
		$this->settings = $settings;
		$this->home_name = $home_name;
		$this->breadcrumbs_chain = $this->formBreadcrumbsChain();
	}

	public function getPath()
	{
		$path = $this->breadcrumbs_chain
			? $this->breadcrumbs_chain->path()
			: array();

		return count($path)
			? array_slice($path, 0, -1)
			: array();
	}

	public function getCurrentPageItem()
	{
		return $this->breadcrumbs_chain->lastItem();
	}

	/**
	 * @return shopBreadcrumbsBreadcrumbsChain
	 */
	private function formBreadcrumbsChain()
	{
		$app = shopBreadcrumbsPlugin::param('app');
		$module = shopBreadcrumbsPlugin::param('module');

		if ($module == 'frontend')
		{
			if ($app == 'shop')
			{
				$shop_breadcrumbs = new shopBreadcrumbsShopBreadcrumbs($this->settings, $this->home_name);

				return $shop_breadcrumbs->getChain();
			}
			elseif ($app == 'blog')
			{
				$blog_breadcrumbs = new shopBreadcrumbsBlogBreadcrumbs($this->settings, $this->home_name);

				return $blog_breadcrumbs->getChain();
			}
		}

		return new shopBreadcrumbsBreadcrumbsChain();
	}
}