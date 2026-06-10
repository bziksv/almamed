<?php

class shopSearchproSettingsStorage
{
	protected static $env;
	protected static $settings_model;
	protected static $settings;

	protected $settings_config;

	public function __construct($env = null)
	{
		if($env !== null && $env instanceof shopSearchproEnv) {
			self::$env = $env;
		}
	}

	protected static function getSettingsModel()
	{
		if(!self::$settings_model) {
			self::$settings_model = new shopSearchproSettingsModel();
		}

		return self::$settings_model;
	}

	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopSearchproEnv();

		return self::$env;
	}

	protected function getSettingsConfig()
	{
		if($this->settings_config === null) {
			$path = wa()->getAppPath('plugins/searchpro/lib/config/settings.php', 'shop');

			if(file_exists($path)) {
				$settings_config = include($path);

				if(!is_array($settings_config)) {
					$settings_config = array();
				}
			} else {
				$settings_config = array();
			}

			$this->settings_config = $settings_config;
		}

		return $this->settings_config;
	}

	public static function getBasicSettings($name = null)
	{
		if(self::$settings === null) {
			self::$settings = self::getSettingsModel()->get();
		}

		if($name === null) {
			return self::$settings['basic'];
		} else {
			return ifset(self::$settings, 'basic', $name, null);
		}
	}

	public function getSettings($name = null, $storefront_id = null)
	{
		if(self::$settings === null) {
			self::$settings = self::getSettingsModel()->get($this->getSettingsConfig());
		}

		return $this->getEnv()->getActiveSettings(self::$settings, $name, $storefront_id);
	}
}