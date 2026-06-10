<?php

class shopBreadcrumbsPlugin extends shopPlugin
{
	private static $params = null;
	private static $extra_breadcrumbs = array();
	private static $override_current = array();

	public function handleFrontendHead()
	{
		self::$params = waRequest::param();
	}

	public static function getBreadcrumbs($breadcrumbs, $category)
	{
		return shopBreadcrumbsViewHelper::getBreadcrumbs();
	}


	public static function param($name, $default_value = null)
	{
		return self::$params === null
			? waRequest::param($name, $default_value)
			: ifset(self::$params[$name], $default_value);
	}


	public static function getStorefront()
	{
		$routing = wa()->getRouting();
		$route = $routing->getRoute();
		$domain = $routing->getDomain();

		return $domain . '/' . $route['url'];
	}

	public function handleFrontendBreadcrumbs($params, $event_name)
	{
		$dot_position = strpos($event_name, '.');
		$breadcrumbs = ifset($params['breadcrumbs']);

		if ($dot_position === false || !is_array($breadcrumbs))
		{
			return;
		}

		$page = substr($event_name, $dot_position + 1);

		self::$extra_breadcrumbs[$page] = $breadcrumbs;
		self::$override_current[$page] = !!ifset($params, 'override_current', false);
	}


	public static function getExtraBreadcrumbs($page)
	{
		return ifset(self::$extra_breadcrumbs[$page], array());
	}

	public static function overrideCurrent($page)
	{
		return ifset(self::$override_current, $page, false);
	}


	public static function getPath($path)
	{
		return wa('shop')->getAppPath('plugins/breadcrumbs/' . $path, 'shop');
	}

	public static function getDataPath($path, $public = false)
	{
		return wa('shop')->getDataPath('plugins/breadcrumbs/', $public, 'shop') . $path;
	}

	public static function getStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getAppStaticUrl('shop', $absolute) . 'plugins/breadcrumbs/' . $url;
	}

	public static function getDataStaticUrl($url, $absolute = false)
	{
		return wa('shop')->getDataUrl('plugins/breadcrumbs/' . $url, true, 'shop', $absolute);
	}
}