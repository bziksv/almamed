<?php

class shopSeofilterRightsConfigHandler extends shopSeofilterHookHandler
{
	private $plugin_user_rights;

	public function __construct($params)
	{
		parent::__construct($params);

		$this->plugin_user_rights = new shopSeofilterUserRights();
	}

	protected function handle()
	{
		$this->plugin_user_rights->updateConfig($this->params);
	}

	protected function beforeHandle()
	{
		return $this->params instanceof waRightConfig;
	}
}