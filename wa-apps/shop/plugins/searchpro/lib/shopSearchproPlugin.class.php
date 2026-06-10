<?php

class shopSearchproPlugin extends shopPlugin
{
	protected static $env;
	protected static $settings_storage;

	/**
	 * @return shopSearchproPlugin
	 */
	public static function getInstance($from = null)
	{
		return wa('shop')->getPlugin('searchpro');
	}

	protected static function getSettingsStorage()
	{
		if(!self::$settings_storage) {
			self::$settings_storage = new shopSearchproSettingsStorage(self::getEnv());
		}

		return self::$settings_storage;
	}

	public static function getEnv()
	{
		if(!self::$env) {
			self::$env = new shopSearchproEnv();
		}

		return self::$env;
	}

	public function getSettings($name = null, $storefront_id = null)
	{
		return self::getSettingsStorage()->getSettings($name, $storefront_id);
	}

	public static function staticallyGetSettings($name = null, $storefront_id = null)
	{
		return self::getSettingsStorage()->getSettings($name, $storefront_id);
	}

	public function routing($route = array())
	{
		return $this->getSettings('status')
			? parent::routing($route)
			: array();
	}
}
