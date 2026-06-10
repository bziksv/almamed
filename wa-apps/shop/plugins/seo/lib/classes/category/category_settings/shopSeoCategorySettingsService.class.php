<?php


class shopSeoCategorySettingsService
{
	private $settings_source;
	private $settings = array();
	
	public function __construct(shopSeoCategorySettingsSource $settings_source)
	{
		$this->settings_source = $settings_source;
	}
	
	/**
	 * @param $group_storefront_id
	 * @param $category_id
	 * @return shopSeoCategorySettings
	 */
	public function getByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id)
	{
		$key = json_encode(array('group_storefront_id' => $group_storefront_id, 'category_id' => $category_id));
		
		if (!isset($this->settings[$key]))
		{
			$rows = $this->settings_source->getByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id);
			$settings = array();
			
			foreach ($rows as $row)
			{
				$settings[$row['name']] = $row['value'];
			}
			
			$category_settings = new shopSeoCategorySettings();
			$category_settings->setGroupStorefrontId($group_storefront_id);
			$category_settings->setCategoryId($category_id);
			
			$category_settings->setSettings($settings);
			
			$this->settings[$key] = $category_settings;
		}
		
		return $this->settings[$key];
	}
	
	public function getGeneralByCategoryId($category_id)
	{
		return $this->getByGroupStorefrontIdAndCategoryId(0, $category_id);
	}
	
	public function store(shopSeoCategorySettings $category_settings)
	{
		$this->settings = array();
		$settings = $category_settings->getSettings();
		$rows = array();
		
		foreach ($settings as $name => $value)
		{
			$rows[] = array('name' => $name, 'value' => $value);
		}
		
		
		$this->settings_source->updateByGroupStorefrontIdAndCategoryId($category_settings->getGroupStorefrontId(), $category_settings->getCategoryId(), $rows);
	}
	
	public function delete(shopSeoCategorySettings $category_settings)
	{
		$this->settings = array();
		$this->settings_source->deleteByGroupStorefrontIdAndCategoryId($category_settings->getGroupStorefrontId(), $category_settings->getCategoryId());
	}
	
	public function deleteByGroupStorefrontId($group_storefront_id)
	{
		$this->settings = array();
		$this->settings_source->deleteByGroupStorefrontId($group_storefront_id);
	}
	
	public function deleteByCategoryId($category_id)
	{
		$this->settings = array();
		$this->settings_source->deleteByCategoryId($category_id);
	}
}