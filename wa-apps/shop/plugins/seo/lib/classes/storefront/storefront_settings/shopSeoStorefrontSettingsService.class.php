<?php


class shopSeoStorefrontSettingsService
{
	private $settings_source;
	private $storefront_settings = array();
	
	public function __construct(shopSeoStorefrontSettingsSource $settings_source)
	{
		$this->settings_source = $settings_source;
	}
	
	/**
	 * @param $group_id
	 * @return shopSeoStorefrontSettings
	 */
	public function getByGroupId($group_id)
	{
		if (!isset($this->storefront_settings[$group_id]))
		{
			$rows = $this->settings_source->getByGroupId($group_id);
			$settings = array();
			
			foreach ($rows as $row)
			{
				$settings[$row['name']] = $row['value'];
			}
			
			$storefront_settings = new shopSeoStorefrontSettings();
			$storefront_settings->setGroupId($group_id);
			
			$storefront_settings->setSettings($settings);
			
			$this->storefront_settings[$group_id] = $storefront_settings;
		}
		
		return $this->storefront_settings[$group_id];
	}
	
	public function getGeneralSettings()
	{
		return $this->getByGroupId(0);
	}
	
	public function store(shopSeoStorefrontSettings $storefront_settings)
	{
		$this->storefront_settings = array();
		$settings = $storefront_settings->getSettings();
		$rows = array();
		
		foreach ($settings as $name => $value)
		{
			$rows[] = array('name' => $name, 'value' => $value);
		}
		
		
		$this->settings_source->updateByGroupId($storefront_settings->getGroupId(), $rows);
	}
	
	public function delete(shopSeoStorefrontSettings $storefront_settings)
	{
		$this->storefront_settings = array();
		$this->settings_source->deleteByGroupId($storefront_settings->getGroupId());
	}
}