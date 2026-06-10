<?php


/**
 * @property string storefront_name
 * @property boolean home_page_is_enabled
 * @property string home_page_meta_title
 * @property string home_page_meta_keywords
 * @property string home_page_meta_description
 * @property string home_page_description
 * @property boolean category_is_enabled
 * @property string category_meta_title
 * @property string category_h1
 * @property string category_meta_keywords
 * @property string category_meta_description
 * @property string category_description
 * @property string category_additional_description
 * @property boolean category_ignore_meta_data
 * @property boolean category_ignore_description
 * @property boolean category_pagination_is_enabled
 * @property string category_pagination_meta_title
 * @property string category_pagination_h1
 * @property string category_pagination_meta_keywords
 * @property string category_pagination_meta_description
 * @property string category_pagination_description
 * @property string category_pagination_additional_description
 * @property boolean category_pagination_ignore_meta_data
 * @property boolean category_pagination_ignore_description
 * @property boolean product_is_enabled
 * @property string product_meta_title
 * @property string product_h1
 * @property string product_meta_keywords
 * @property string product_meta_description
 * @property string product_description
 * @property string product_additional_description
 * @property boolean product_ignore_meta_data
 * @property boolean product_ignore_description
 * @property boolean product_review_is_enabled
 * @property string product_review_meta_title
 * @property string product_review_meta_keywords
 * @property string product_review_meta_description
 * @property boolean product_page_is_enabled
 * @property string product_page_meta_title
 * @property string product_page_h1
 * @property string product_page_meta_keywords
 * @property string product_page_meta_description
 * @property boolean product_page_ignore_meta_data
 * @property boolean page_is_enabled
 * @property string page_meta_title
 * @property string page_meta_keywords
 * @property string page_meta_description
 * @property boolean page_ignore_meta_data
 * @property boolean tag_is_enabled
 * @property string tag_meta_title
 * @property string tag_meta_keywords
 * @property string tag_meta_description
 * @property string tag_description
 * @property boolean brand_is_enabled
 * @property string brand_meta_title
 * @property string brand_h1
 * @property string brand_meta_keywords
 * @property string brand_meta_description
 * @property string brand_description
 * @property boolean brand_ignore_meta_data
 * @property boolean brand_category_is_enabled
 * @property string brand_category_meta_title
 * @property string brand_category_h1
 * @property string brand_category_meta_keywords
 * @property string brand_category_meta_description
 * @property string brand_category_description
 */
class shopSeoStorefrontSettings implements shopSeoSettings
{
	private $group_id;
	private $settings;
	
	public function __construct()
	{
		$this->settings = new shopSeoSettingsData(array(
			'storefront_name' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'home_page_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'home_page_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'home_page_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'home_page_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'home_page_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_ignore_description' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_pagination_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_pagination_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_pagination_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_pagination_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_pagination_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_pagination_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_pagination_additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'category_pagination_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'category_pagination_ignore_description' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_ignore_description' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_review_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_review_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_review_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_review_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_page_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'product_page_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_page_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_page_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_page_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'product_page_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'page_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'page_meta_title' => array(
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
			'tag_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'tag_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'tag_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'tag_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'tag_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'brand_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'brand_category_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'brand_category_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_category_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_category_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_category_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'brand_category_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
		));
	}
	
	public function getGroupId()
	{
		return $this->group_id;
	}
	
	public function setGroupId($group_id)
	{
		$this->group_id = $group_id;
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