<?php


class shopSeoPluginSettingsService
{
	private $settings_source;
	private $hidden_settings_source;

	private $settings;
	private $hidden_settings;

	public function __construct(shopSeoPluginSettingsSource $settings_source, shopSeoPluginHiddenSettingsSource $hidden_settings_source)
	{
		$this->settings_source = $settings_source;
		$this->hidden_settings_source = $hidden_settings_source;
	}

	public function getSettings()
	{
		if (!isset($this->settings))
		{
			$rows = $this->settings_source->getSettings();
			$settings = array();
			
			foreach ($rows as $row)
			{
				$settings[$row['name']] = $row['value'];
			}
			
			$general_settings = new shopSeoPluginSettings();
			$general_settings->setSettings($settings);
			$this->settings = $general_settings;
		}
		
		return $this->settings;
	}

	public function getHiddenSettings()
	{
		if (!isset($this->hidden_settings))
		{
			$hidden_settings_assoc = $this->hidden_settings_source->getSettings();

			$this->hidden_settings = $this->buildHiddenSettings($hidden_settings_assoc);
		}

		return $this->hidden_settings;
	}
	
	public function store(shopSeoPluginSettings $plugin_settings)
	{
		$this->settings = null;
		$settings = $plugin_settings->getSettings();
		$rows = array();
		
		foreach ($settings as $name => $value)
		{
			$rows[] = array('name' => $name, 'value' => $value);
		}
		
		$this->settings_source->updateSettings($rows);
	}

	private function buildHiddenSettings($settings_assoc)
	{
		$settings = new shopSeoPluginHiddenSettings();

		$settings->setCacheForSingleStorefront($settings_assoc['cache_for_single_storefront']);

		return $settings;
	}
}
