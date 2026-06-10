<?php

/**
 * Class shopSeofilterProductfiltersCategorySettings
 * @property bool $is_enabled
 */
class shopSeofilterProductfiltersCategorySettings
{
	/** @var shopSeofilterProductfiltersCategorySettingsModel */
	private static $model = null;
	private static $storefront_settings = null;

	private $storefront;
	private $category_id;

	public function __construct($category_id, $storefront = null)
	{
		if ($storefront === null)
		{
			$storefront = shopSeofilterProductfiltersHelper::getStorefront();
		}

		if (self::$model === null)
		{
			self::$model = new shopSeofilterProductfiltersCategorySettingsModel();
		}

		if (!isset(self::$storefront_settings[$storefront]))
		{
			self::$storefront_settings[$storefront] = array();
		}

		if (!isset(self::$storefront_settings[$storefront][$category_id]))
		{
			self::$storefront_settings[$storefront][$category_id] = array_merge(
				self::$model->getCategorySettings(shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL, $category_id),
				self::$model->getCategorySettings($storefront, $category_id)
			);
		}

		$this->storefront = $storefront;
		$this->category_id = $category_id;
	}

	function __get($setting_name)
	{
		$settings = self::$storefront_settings[$this->storefront][$this->category_id];
		$default_settings = self::$model->getDefaultSettings();

		if (!array_key_exists($setting_name, $default_settings))
		{
			throw new waException("shopSeofilterProductfiltersCategorySettings: unknown setting name [{$setting_name}]");
		}

		return array_key_exists($setting_name, $settings)
			? $settings[$setting_name]
			: $default_settings[$setting_name];
	}

	public function getSettings()
	{
		return self::$storefront_settings[$this->storefront];
	}
}