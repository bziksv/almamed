<?php

abstract class shopSeofilterSettings
{
	const DB_TRUE = '1';
	const DB_FALSE = '0';

	private $_settings;

	public function __construct($settings)
	{
		$this->_settings = $settings;

		foreach ($this->defaultSettings() as $field => $default_value)
		{
			$this->_settings[$field] = array_key_exists($field, $settings)
				? $settings[$field]
				: $default_value;
		}
	}

	function __get($name)
	{
		return array_key_exists($name, $this->_settings)
			? $this->prepareSettingValue($name, $this->_settings[$name])
			: null;
	}

	public function getRawSettings()
	{
		return $this->_settings;
	}

	protected function prepareSettingValue($name, $value)
	{
		$boolean_settings = $this->booleanSettingsFields();

		return isset($boolean_settings[$name])
			? $value == self::DB_TRUE
			: $value;
	}

	/**
	 * @return array
	 */
	protected abstract function defaultSettings();
	/**
	 * @return array
	 */
	protected abstract function booleanSettingsFields();
}