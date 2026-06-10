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
		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);
		$this->wa_settings_page->save($state, $loaded_groups_storefronts_ids, $loaded_groups_categories_ids);
		
		$this->response = $this->wa_settings_page->getState($loaded_groups_storefronts_ids, $loaded_groups_categories_ids);
	}
}