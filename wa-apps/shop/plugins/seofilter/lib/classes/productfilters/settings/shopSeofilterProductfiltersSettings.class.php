<?php

/**
 * Class shopSeofilterProductfiltersSettings
 * @property bool $is_enabled
 * @property $link_type
 * @property $custom_link_text
 * @property $open_link_in_new_tab
 */
class shopSeofilterProductfiltersSettings
{
	const STOREFRONT_GENERAL = '*';

	const LINK_TYPE_VALUE = '0';
	const LINK_TYPE_OTHER_PRODUCTS = '1';
	const LINK_TYPE_CUSTOM_TEXT = '2';

	const OPEN_LINK_IN_SAME_TAB = '0';
	const OPEN_LINK_IN_NEW_TAB = '1';

	private static $raw_settings = array();

	private $storefront;

	public function __construct($storefront = null)
	{
		$this->storefront = $storefront === null
			? shopSeofilterProductfiltersHelper::getStorefront()
			: $storefront;

		if (!isset(self::$raw_settings[$this->storefront]))
		{
			$model = new shopSeofilterProductfiltersSettingsModel();
			self::$raw_settings[$this->storefront] = $model->getRawSettings($this->storefront);
		}
	}

	function __get($field)
	{
		$default_settings = $this->defaultSettings();

		if (!array_key_exists($field, $default_settings))
		{
			throw new waException("shopSeofilterProductfiltersSettings: unknown settings field [{$field}]");
		}

		return $this->prepareValue($field);
	}

	private function prepareValue($field)
	{
		$boolean_fields = $this->booleanFields();
		$default_settings = $this->defaultSettings();

		if (!array_key_exists($field, self::$raw_settings[$this->storefront]))
		{
			return $default_settings[$field];
		}
		elseif (array_key_exists($field, $boolean_fields))
		{
			return self::$raw_settings[$this->storefront][$field] == shopSeofilterProductfiltersSettingsModel::DB_TRUE;
		}
		else
		{
			return self::$raw_settings[$this->storefront][$field];
		}
	}

	public function booleanFields()
	{
		return array(
			'is_enabled' => 'is_enabled',
		);
	}

	private function defaultSettings()
	{
		return array(
			'is_enabled' => true,
			'link_type' => self::LINK_TYPE_VALUE,
			'custom_link_text' => 'другие товары',
			'open_link_in_new_tab' => self::OPEN_LINK_IN_SAME_TAB,
		);
	}

	public function getSettings()
	{
		$default_settings = $this->defaultSettings();

		$settings = array();

		foreach ($default_settings as $field => $default_value)
		{
			$settings[$field] = $this->prepareValue($field);
		}

		return $settings;
	}
}
