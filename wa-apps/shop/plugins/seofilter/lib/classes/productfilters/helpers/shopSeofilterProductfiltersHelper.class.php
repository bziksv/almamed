<?php

class shopSeofilterProductfiltersHelper
{
	private static $storefront = null;

	public static function getStorefront()
	{
		if (self::$storefront === null)
		{
			$routing = wa()->getRouting();
			$route = $routing->getRoute();
			$domain = $routing->getDomain();

			self::$storefront = $domain . '/' . $route['url'];
		}

		return self::$storefront;
	}

	public static function getCurrency()
	{
		/** @var shopConfig $shop_config */
		$shop_config = wa('shop')->getConfig();

		return $shop_config instanceof shopConfig
			? $shop_config->getCurrency()
			: 'RUB';
	}

	public static function getPath($path)
	{
		return wa('shop')->getAppPath('plugins/seofilter/' . $path, 'shop');
	}

	public static function getDataPath($path, $public = false)
	{
		return wa('shop')->getDataPath('plugins/seofilter/', $public, 'shop') . $path;
	}

	public static function getStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getAppStaticUrl('shop', $absolute) . 'plugins/seofilter/' . $url;
	}

	public static function getDataStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getDataUrl('plugins/seofilter/' . $url, true, 'shop', $absolute);
	}
}