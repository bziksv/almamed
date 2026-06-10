<?php

class shopBreadcrumbsProductbrandsBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $brand;

	public function __construct($brand)
	{
		$this->brand = $brand;
	}

	protected function initializeItems()
	{
		$items = array(
			array(
				'name' => 'Бренды',
				'url' => wa()->getRouteUrl('shop/frontend/brands'),
			)
		);

		if (is_array($this->brand))
		{
			$seo_extender = new shopBreadcrumbsSeoExtender();

			$brand = $seo_extender->extendBrand($this->brand);

			$items[] = array(
				'name' => $brand['name'],
				'url' => wa()->getRouteUrl('shop/frontend/brand', array('brand' => $brand['name'])),
			);
		}

		return $items;
	}
}