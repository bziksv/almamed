<?php

class shopSeofilterHelper
{
	private static $is_seofilter_installed = null;
	private static $is_linkcanonical_enabled = null;
	private static $is_smartfilters_enabled = null;
	private static $is_productbrands_enabled = null;

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

	public static function isSeofilterInstalled()
	{
		if (self::$is_seofilter_installed === null)
		{
			$info = wa('shop')->getConfig()->getPluginInfo('seofilter');

			self::$is_seofilter_installed = is_array($info) && count($info) > 0;
		}

		return self::$is_seofilter_installed;
	}
	
	public static function isLinkcanonicalPluginInstalled()
	{
		if (self::$is_linkcanonical_enabled === null)
		{
			$info = wa('shop')->getConfig()->getPluginInfo('linkcanonical');

			$is_linkcanonical_enabled = true;
			
			if ($info === array() || !array_key_exists('version', $info))
			{
				$is_linkcanonical_enabled = false;
			}
			else
			{
				$version = $info['version'];

				if (version_compare($version, '1.10', '<'))
				{
					$is_linkcanonical_enabled = false;
				}
			}

			self::$is_linkcanonical_enabled = $is_linkcanonical_enabled;
		}
		
		return self::$is_linkcanonical_enabled;
	}

	public static function isSmartfiltersPluginEnabled()
	{
		if (!self::isSmartfiltersPluginInstalled())
		{
			return false;
		}

		$app_settings_model = new waAppSettingsModel();
		return !!$app_settings_model->get('shop.smartfilters', 'enabled');
	}

	public static function isSmartfiltersPluginInstalled()
	{
		if (self::$is_smartfilters_enabled === null)
		{
			$info = wa('shop')->getConfig()->getPluginInfo('smartfilters');

			$is_smartfilters_enabled = true;

			if ($info === array() || !array_key_exists('version', $info))
			{
				$is_smartfilters_enabled = false;
			}
			else
			{
				$version = $info['version'];

				if (version_compare($version, '2.0', '<') || !class_exists('shopSmartfiltersPlugin') || !method_exists('shopSmartfiltersPlugin', 'getFiltersForCategory'))
				{
					$is_smartfilters_enabled = false;
				}
			}

			self::$is_smartfilters_enabled = $is_smartfilters_enabled;
		}

		return self::$is_smartfilters_enabled;
	}

	public static function getViewFilters($category_id)
	{
		$filters = array();

		if (self::isSmartfiltersPluginEnabled())
		{
			$filters = shopSmartfiltersPlugin::getFiltersForCategory($category_id);
		}

		if (!is_array($filters) || count($filters) === 0)
		{
			$filters = wa()->getView()->getVars('filters');
		}

		return $filters;
	}

	public static function isProductbrandsPluginInstalled()
	{
		if (self::$is_productbrands_enabled === null)
		{
			$info = wa('shop')->getConfig()->getPluginInfo('productbrands');

			$is_productbrands_enabled = true;

			if ($info === array())
			{
				$is_productbrands_enabled = false;
			}

			self::$is_productbrands_enabled = $is_productbrands_enabled;
		}

		return self::$is_productbrands_enabled;
	}
}
