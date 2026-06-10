<?php

class shopSeofilterCatalogreviewsPluginHelper
{
	const PLUGIN_ID = 'catalogreviews';

	private static $info = null;
	private static $plugin_instance = false;

	public function isPluginInstalled()
	{
		return $this->getPluginInfo() !== array();
	}

	public function isEnabled()
	{
		if (!$this->isPluginInstalled())
		{
			return false;
		}

		$catalogreviews_plugin = $this->getPluginInstance();
		if (!$catalogreviews_plugin)
		{
			return false;
		}

		return $catalogreviews_plugin->getEnv()->getConfig()->plugin_is_enabled;
	}

	/**
	 * @return shopCatalogreviewsPluginEnv|null
	 */
	public function getPluginEnv()
	{
		if (!$this->isEnabled())
		{
			return null;
		}

		return $this->getPluginInstance()->getEnv();
	}

	public function isCurrentPageReviewsCatalogPage()
	{
		$plugin_env = $this->getPluginEnv();

		return $plugin_env
			? $plugin_env->isReviewsCatalogPage()
			: false;
	}

	public function isFilterEnabledOnCategory($category_id, $filter_id)
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		$context = shopCatalogreviewsContext::getFrontendInstance();
		$category_seofilter_settings_storage = $context->getCategorySeofilterSettingsStorage();

		$settings = $category_seofilter_settings_storage->fetchSettings($category_id);

		return !in_array($filter_id, $settings->disabled_seofilter_filter_ids);
	}

	public function transformCategorySeofilterUrlToCategoryReviewsUrl($seofilter_url, $absolute = false)
	{
		$plugin_env = $this->getPluginEnv();
		if (!$plugin_env)
		{
			return '';
		}
		$catalogreviews_plugin_routing = $plugin_env->getPluginRouting();

		return $catalogreviews_plugin_routing->transformCategorySeofilterUrlToCategoryReviewsUrl($seofilter_url, $absolute);
	}

	public function getCurrentPageCategory()
	{
		$plugin_env = $this->getPluginEnv();

		return $plugin_env
			? $plugin_env->getCategory()
			: '';
	}

	private function getPluginInfo()
	{
		if (self::$info === null)
		{
			self::$info = wa('shop')->getConfig()->getPluginInfo(self::PLUGIN_ID);
		}

		return self::$info;
	}

	/**
	 * @return shopCatalogreviewsPlugin|null
	 */
	private function getPluginInstance()
	{
		if (!$this->isPluginInstalled())
		{
			return null;
		}

		if (self::$plugin_instance === false)
		{
			try
			{
				self::$plugin_instance = wa('shop')->getPlugin(self::PLUGIN_ID);
			}
			catch (Exception $e)
			{
				self::$plugin_instance = null;
			}
		}

		return self::$plugin_instance;
	}
}