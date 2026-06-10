<?php

class shopSearchproPluginViewHelper
{
	public static $groups = array(
		'products' => 'Товары',
		'categories' => 'Категории',
		'brands' => 'Бренды',
		'popular' => 'Популярные запросы',
		'history' => 'История запросов'
	);

	public static function getGroupTitle($group)
	{
		return ifset(self::$groups, $group, null);
	}

	public static function field($params = array())
	{
		$frontend = new shopSearchproFrontend(null, shopSearchproPlugin::staticallyGetSettings(), shopSearchproPlugin::getEnv());
		$output = $frontend->field($params);

		return $output;
	}

	protected static function getRoute()
	{
		$route = wa()->getRouting()->getRoute();
		$route['full_url'] = wa()->getRouting()->getDomain(null, true).'/'.$route['url'];

		return $route;
	}

	protected static function createProductUrl($product, $key = '', $route_params = array())
	{
		$route_params['product_url'] = $product['url'];

		if(isset($product['category_url'])) {
			$route_params['category_url'] = $product['category_url'];
		} else {
			$route_params['category_url'] = '';
		}

		return wa()->getRouteUrl('shop/frontend/product'.ucfirst($key), $route_params);
	}

	public static function getProductUrl(&$product)
	{
		if(!$product instanceof shopProduct) {
			if(!array_key_exists('category_full_url', $product) || !array_key_exists('category_url', $product)) {
				$product = new shopProduct($product['id']);

				return self::getProductUrl($product);
			}

			$route = self::getRoute();

			$product['category_url'] = (ifset($route['url_type']) == 1) ? $product['category_url'] : $product['category_full_url'];
		}

		return self::createProductUrl($product);

		// if(!array_key_exists('category_full_url', $product))
	}
}
