<?php


/**
 * @property boolean is_enabled
 * @property boolean category_subcategories_is_enabled
 * @property boolean category_products_is_enabled
 * @property boolean category_pagination_is_enabled
 * @property boolean category_product_h1_is_enabled
 * @property boolean product_review_is_enabled
 * @property boolean product_page_is_enabled
 * @property boolean category_additional_description_is_enabled
 * @property boolean product_additional_description_is_enabled
 * @property boolean page_number_is_enabled
 * @property boolean sort_is_enabled
 * @property boolean cache_is_enabled
 * @property string cache_variant
 */
class shopSeoPluginSettings implements shopSeoSettings
{
	const CACHE_VARIANT_30_MINUTES = '30';
	const CACHE_VARIANT_3_HOURS = '180';
	const CACHE_VARIANT_1_DAY = '1440';
	const CACHE_VARIANT_7_DAYS = '10080';
	const CACHE_VARIANT_30_DAYS = '43200';
	
	private $settings;
	
	public function __construct()
	{
		$this->settings = new shopSeoSettingsData(array(
			'is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_subcategories_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_products_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_pagination_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_product_h1_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_review_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_page_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_additional_description_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_additional_description_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'page_number_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'sort_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'cache_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'cache_variant' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => self::CACHE_VARIANT_30_MINUTES,
			),
		));
	}
	
	public function getMetaData()
	{
		return $this->settings->getMetaData();
	}
	
	public function getSettings()
	{
		return $this->settings->getSettings();
	}
	
	public function setSettings($settings)
	{
		$this->settings->setSettings($settings);
	}
	
	public function __get($name)
	{
		return $this->settings->__get($name);
	}
	
	public function __set($name, $value)
	{
		$this->settings->__set($name, $value);
	}
}