<?php

class shopSeofilterStorefrontModel
{
	protected static $storefronts;
	protected static $current_storefront;
	protected static $domains = null;

	/**
	 * @return string[]
	 */
	public static function getStorefronts()
	{
		if (!is_array(self::$domains))
		{
			self::$domains = wa()->getRouting()->getByApp('shop');
		}

		if (!isset(self::$storefronts))
		{
			self::$storefronts = array();

			foreach (self::$domains as $_domain => $domain_routes)
			{
				foreach ($domain_routes as $_route)
				{
					self::$storefronts[] = $_domain . '/' . $_route['url'];
				}
			}
		}

		return self::$storefronts;
	}

	public static function countStorefronts()
	{
		return count(self::getStorefronts());
	}

	public static function getAllStorefrontParams()
	{
		if (!is_array(self::$domains))
		{
			self::$domains = wa()->getRouting()->getByApp('shop');
		}

		$result = array();

		foreach (self::$domains as $domain => $domain_routes)
		{
			foreach ($domain_routes as $route)
			{
				//yield array(
				//	'storefront' => $domain . '/' . $route['url'],
				//	'domain' => $domain,
				//	'route' => $route,
				//);

				$result[] = array(
					'storefront' => $domain . '/' . $route['url'],
					'domain' => $domain,
					'route' => $route,
				);
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getCurrentStorefront()
	{
		$storefronts = self::getStorefronts();
		$routing = wa()->getRouting();
		$domain = $routing->getDomain();
		$route = $routing->getRoute();
		$storefront = $domain . '/' . $route['url'];

		return in_array($storefront, $storefronts) ? $storefront : null;
	}
}
