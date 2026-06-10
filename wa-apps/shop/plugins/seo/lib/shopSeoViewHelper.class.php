<?php

class shopSeoViewHelper
{
	public static function extendCategory($category, $storefront = null)
	{
		if (!isset($storefront))
		{
			$storefront = self::getCurrentStorefront();
		}
		
		$category_extender = shopSeoContext::getInstance()->getCategoryExtender();
		
		return $category_extender->extend($storefront, $category, waRequest::get('page', 1));
	}
	
	public static function extendProduct($product, $storefront = null)
	{
		if (!isset($storefront))
		{
			$storefront = self::getCurrentStorefront();
		}
		
		$product_extender = shopSeoContext::getInstance()->getProductExtender();
		
		return $product_extender->extend($storefront, $product);
	}
	
	public static function getContext()
	{
		return shopSeoContext::getInstance();
	}
	
	private static function getCurrentStorefront()
	{
		$storefront_service = shopSeoContext::getInstance()->getStorefrontService();
		
		return $storefront_service->getCurrentStorefront();
	}
}