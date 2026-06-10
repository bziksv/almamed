<?php


class shopSeoPluginSettingsModel extends waModel implements shopSeoPluginSettingsSource
{
	protected $table = 'shop_seo_plugin_settings';
	
	public function getSettings()
	{
		return $this->getAll();
	}
	
	public function updateSettings($rows)
	{
		$this->truncate();
		
		foreach ($rows as $row)
		{
			$this->insert($row);
		}
	}
}