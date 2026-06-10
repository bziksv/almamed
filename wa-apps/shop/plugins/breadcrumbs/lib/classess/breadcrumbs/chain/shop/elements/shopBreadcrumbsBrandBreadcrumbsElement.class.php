<?php

class shopBreadcrumbsBrandBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	/** @var shopBrandBrand */
	private $brand;

	/** @var shopBrandPage */
	private $page;

	public function __construct($brand, $page)
	{
		$this->brand = $brand;
		$this->page = $page;
	}

	protected function initializeItems()
	{
		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'brands',
		);

		$items = array(
			array(
				'name' => 'Бренды',
				'url' => wa()->getRouteUrl('shop', $route_params),
			)
		);

		if (!($this->brand instanceof shopBrandBrand))
		{
			return $items;
		}

		$items[] = array(
			'name' => $this->brand->name,
			'url' => $this->brand->getFrontendUrl(),
		);

		if (($this->page instanceof shopBrandPage) && !$this->page->isMain())
		{
			$items[] = array(
				'name' => $this->page->name,
				'url' => $this->brand->getFrontendUrl($this->page),
			);
		}

		return $items;
	}
}