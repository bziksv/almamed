<?php

class shopBreadcrumbsSeobrandBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	/** @var shopSeobrandBrand */
	private $brand;

	/** @var shopSeobrandBrandPage */
	private $brand_page;

	public function __construct($brand, $brand_page)
	{
		$this->brand = $brand;
		$this->brand_page = $brand_page;
	}

	protected function initializeItems()
	{
		$route_params = array(
			'plugin' => 'seobrand',
			'module' => 'frontend',
			'action' => 'brands',
		);

		$items = array(
			array(
				'name' => 'Бренды',
				'url' => wa()->getRouteUrl('shop', $route_params),
			)
		);

		if ($this->brand instanceof shopSeobrandBrand)
		{
			$seo_extender = new shopBreadcrumbsSeoExtender();

			$brand = $seo_extender->extendBrand($this->brand);

			$items[] = array(
				'name' => $brand['name'],
				'url' => $this->brand->frontend_url,
			);
		}

		if (($this->brand_page instanceof shopSeobrandBrandPage) && $this->brand_page->url != shopSeobrandPluginFrontendBrandPageAction::PAGE_CATALOG)
		{
			$items[] = array(
				'name' => $this->brand_page->name,
				'url' => $this->brand_page->getFrontendUrl($this->brand),
			);
		}

		return $items;
	}
}