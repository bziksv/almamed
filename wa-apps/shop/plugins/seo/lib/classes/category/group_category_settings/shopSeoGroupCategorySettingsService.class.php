<?php


class shopSeoGroupCategorySettingsService
{
	private $settings_source;
	
	public function __construct(shopSeoGroupCategorySettingsSource $settings_source)
	{
		$this->settings_source = $settings_source;
	}
	
	public function getByGroupId($group_id)
	{
		$rows = $this->settings_source->getByGroupId($group_id);
		$settings = array();
		
		foreach ($rows as $row)
		{
			$settings[$row['name']] = $row['value'];
		}
		
		$category_settings = new shopSeoGroupCategorySettings();
		$category_settings->setGroupId($group_id);
		
		$category_settings->setSettings($settings);
		
		return $category_settings;
	}
	
	public function store(shopSeoGroupCategorySettings $category_settings)
	{
		$settings = $category_settings->getSettings();
		$rows = array();
		
		foreach ($settings as $name => $value)
		{
			$rows[] = array('name' => $name, 'value' => $value);
		}
		
		
		$this->settings_source->updateByGroupId($category_settings->getGroupId(), $rows);
	}
	
	public function delete(shopSeoGroupCategorySettings $category_settings)
	{
		$this->settings_source->deleteByGroupId($category_settings->getGroupId());
	}
}