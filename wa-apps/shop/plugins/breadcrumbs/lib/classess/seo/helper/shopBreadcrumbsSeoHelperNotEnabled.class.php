<?php

class shopBreadcrumbsSeoHelperNotEnabled extends shopBreadcrumbsAbstractSeoHelper
{
	public function isPluginEnabled()
	{
		return false;
	}

	public function getCategoryH1($storefront, $category)
	{
		return $category['name'];
	}

	public function getCategorySeoName($storefront, $category)
	{
		return '';
	}

	public function getProductH1($storefront, $product)
	{
		return $product['name'];
	}

	public function getProductPageH1($storefront, $product, $page)
	{
		return $page['title'];
	}

	public function getShopPageH1($storefront, $page)
	{
		return $page['name'];
	}

	public function getBrandH1($storefront, $brand)
	{
		return $brand['name'];
	}
}