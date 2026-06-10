<?php

abstract class shopSeofilterHookHandler
{
	private static $_settings = null;

	protected $params;

	/** @var shopSeofilterPluginSettings */
	protected $settings = null;
	/** @var shopSeofilterRouting */
	protected $plugin_routing = null;
	/** @var shopSeofilterPluginEnvironment */
	protected $plugin_environment = null;

	public function __construct($params = null)
	{
		if (self::$_settings === null)
		{
			self::$_settings = shopSeofilterBasicSettingsModel::getSettings();
		}

		$this->settings = self::$_settings;
		$this->plugin_routing = shopSeofilterRouting::instance();
		$this->plugin_environment = shopSeofilterPluginEnvironment::instance();

		$this->params = $params;
	}

	public function run()
	{
		return $this->beforeHandle()
			? $this->handle()
			: $this->defaultHandleResult();
	}

	protected abstract function handle();

	protected function beforeHandle()
	{
		return $this->settings->is_enabled;
	}

	protected function defaultHandleResult()
	{
		return '';
	}
}