<?php

class shopSeoPluginSettingsAction extends waViewAction
{
	private $wa_settings_page;
	
	public function __construct($params = null)
	{
		parent::__construct($params);
		
		$this->wa_settings_page = shopSeoContext::getInstance()->getWaSettingsPage();
	}
	
	public function execute()
	{
		$plugin = wa('shop')->getPlugin('seo');
		
		$this->view->assign('state', $this->wa_settings_page->getState(array(), array()));
		$this->view->assign('version', $plugin->getVersion());
	}
}
