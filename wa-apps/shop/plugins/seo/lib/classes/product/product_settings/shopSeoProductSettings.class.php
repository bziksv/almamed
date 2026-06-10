<?php


/**
 * @property string seo_name
 * @property string meta_title
 * @property string h1
 * @property string meta_keywords
 * @property string meta_description
 * @property string description
 * @property string additional_description
 * @property string ignore_meta_data
 * @property string ignore_description
 * @property boolean review_is_enabled
 * @property string review_meta_title
 * @property string review_meta_keywords
 * @property string review_meta_description
 * @property boolean page_is_enabled
 * @property string page_meta_title
 * @property string page_h1
 * @property string page_meta_keywords
 * @property string page_meta_description
 * @property string page_ignore_meta_data
 */
class shopSeoProductSettings implements shopSeoSettings
{
	private $group_storefront_id;
	private $product_id;
	private $settings;
	
	public function __construct()
	{
		$this->settings = new shopSeoSettingsData(array(
			'seo_name' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'ignore_description' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'review_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'review_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'review_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'review_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'page_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'page_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'page_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'page_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'page_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'page_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
		));
	}
	
	public function getGroupStorefrontId()
	{
		return $this->group_storefront_id;
	}
	
	public function setGroupStorefrontId($group_storefront_id)
	{
		$this->group_storefront_id = $group_storefront_id;
	}
	
	public function getProductId()
	{
		return $this->product_id;
	}
	
	public function setProductId($product_id)
	{
		$this->product_id = $product_id;
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