<?php


class shopSeoPluginSettingsSaveController extends waJsonController
{
	private $wa_settings_page;
	
	public function __construct()
	{
		$this->wa_settings_page = shopSeoContext::getInstance()->getWaSettingsPage();
	}
	
	public function execute()
	{
		$plugin = shopUserlogPlugin::getInstance();
		$settings_before = null;
		if ($plugin && shopUserlogSeoSnapshot::isAvailable()) {
			$state = shopUserlogSeoSnapshot::capturePluginSettingsState();
			$settings_before = shopUserlogSeoSnapshot::flattenPluginSettingsState($state);
		}

		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);
		$this->wa_settings_page->save($state, $loaded_groups_storefronts_ids, $loaded_groups_categories_ids);
		
		$this->response = $this->wa_settings_page->getState($loaded_groups_storefronts_ids, $loaded_groups_categories_ids);

		if ($plugin && $settings_before !== null) {
			$after = shopUserlogSeoSnapshot::flattenPluginSettingsState($this->response);
			$plugin->logSettingsChange('SEO (плагин)', $settings_before, $after);
		}
	}
}