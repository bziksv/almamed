<?php


/**
 * @property boolean data_is_enabled
 * @property string meta_title
 * @property string h1
 * @property string meta_keywords
 * @property string meta_description
 * @property string description
 * @property string additional_description
 * @property boolean pagination_is_enabled
 * @property string pagination_meta_title
 * @property string pagination_h1
 * @property string pagination_meta_keywords
 * @property string pagination_meta_description
 * @property string pagination_description
 * @property string pagination_additional_description
 * @property boolean subcategory_is_enabled
 * @property string subcategory_meta_title
 * @property string subcategory_h1
 * @property string subcategory_meta_keywords
 * @property string subcategory_meta_description
 * @property string subcategory_description
 * @property string subcategory_additional_description
 * @property boolean subcategory_ignore_meta_data
 * @property boolean subcategory_ignore_description
 * @property boolean subcategory_pagination_is_enabled
 * @property string subcategory_pagination_meta_title
 * @property string subcategory_pagination_h1
 * @property string subcategory_pagination_meta_keywords
 * @property string subcategory_pagination_meta_description
 * @property string subcategory_pagination_description
 * @property string subcategory_pagination_additional_description
 * @property boolean subcategory_pagination_ignore_meta_data
 * @property boolean subcategory_pagination_ignore_description
 * @property boolean product_is_enabled
 * @property string product_meta_title
 * @property string product_h1
 * @property string product_meta_keywords
 * @property string product_meta_description
 * @property string product_description
 * @property string product_additional_description
 * @property string product_ignore_meta_data
 * @property string product_ignore_description
 * @property boolean product_review_is_enabled
 * @property string product_review_meta_title
 * @property string product_review_meta_keywords
 * @property string product_review_meta_description
 * @property boolean product_page_is_enabled
 * @property string product_page_meta_title
 * @property string product_page_h1
 * @property string product_page_meta_keywords
 * @property string product_page_meta_description
 * @property string product_page_ignore_meta_data
 */
class shopSeoGroupCategorySettings implements shopSeoSettings
{
	private $group_id;
	private $settings;
	
	public function __construct()
	{
		$this->settings = new shopSeoSettingsData(array(
			'data_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
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
			'pagination_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'pagination_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'pagination_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'pagination_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'pagination_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'pagination_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'pagination_additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'subcategory_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'subcategory_ignore_description' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'subcategory_pagination_is_enabled' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'subcategory_pagination_meta_title' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_pagination_h1' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_pagination_meta_keywords' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_pagination_meta_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_pagination_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_pagination_additional_description' => array(
				'type' => shopSeoSettingsData::TYPE_TEXT,
				'default' => '',
			),
			'subcategory_pagination_ignore_meta_data' => array(
				'type' => shopSeoSettingsData::TYPE_BOOLEAN,
				'default' => false,
			),
			'subcategory_pagination_ignore_description' => array(
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