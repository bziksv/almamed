<?php

abstract class shopBreadcrumbsAbstractSeoHelper
{
	abstract public function isPluginEnabled();

	abstract public function getCategoryH1($storefront, $category);

	abstract public function getCategorySeoName($storefront, $category);

	abstract public function getProductH1($storefront, $product);

	abstract public function getProductPageH1($storefront, $product, $page);

	abstract public function getShopPageH1($storefront, $page);

	abstract public function getBrandH1($storefront, $brand);
}