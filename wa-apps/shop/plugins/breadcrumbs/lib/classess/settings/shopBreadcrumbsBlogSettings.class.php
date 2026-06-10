<?php

/**
 * Class shopBreadcrumbsBlogSettings
 *
 * @property string $current_level_mode
 * @property string $root_element_app
 * @property bool $hide_current_item
 * @property bool $show_current_item_link
 *
 * @property bool $show_subcategories
 * @property bool $show_subcategories_on_hover
 */
class shopBreadcrumbsBlogSettings
{
	const CURRENT_LEVEL_MODE_NONE = 'NONE';
	const CURRENT_LEVEL_MODE_SHOW = 'SHOW';
	const CURRENT_LEVEL_MODE_SHOW_AS_LINK = 'SHOW_AS_LINK';

	private static $settings_raw = null;

	public function __construct()
	{
		$settings_model = new shopBreadcrumbsBlogSettingsModel();

		if (self::$settings_raw === null)
		{
			self::$settings_raw = $settings_model->getSettings();
		}
	}

	function __get($field)
	{
		$all_fields = $this->settingsFields();

		if ($field == 'hide_current_item')
		{
			return $this->current_level_mode == self::CURRENT_LEVEL_MODE_NONE;
		}
		elseif ($field == 'show_current_item_link')
		{
			return $this->current_level_mode == self::CURRENT_LEVEL_MODE_SHOW_AS_LINK;
		}
		elseif ($field == 'show_subcategories_on_hover' || $field == 'show_subcategories')
		{
			return false;
		}
		elseif (!array_key_exists($field, $all_fields))
		{
			throw new waException("shopBreadcrumbs plugin: unknown settings field [{$field}]");
		}

		return $this->getFieldValue($field);
	}

	private function settingsFields()
	{
		return array(
			'root_element_app' => 'root_element_app',
			'current_level_mode' => 'current_level_mode',
		);
	}

	private function booleanSettingsFields()
	{
		return array();
	}

	private function settingsFieldsDefaultValues()
	{
		return array(
			'root_element_app' => 'shop',
			'current_level_mode' => self::CURRENT_LEVEL_MODE_SHOW,
		);
	}

	private function getFieldValue($field)
	{
		if (isset(self::$settings_raw[$field]))
		{
			$raw_value = self::$settings_raw[$field];

			$bool_fields = $this->booleanSettingsFields();

			if (isset($bool_fields[$field]))
			{
				return $raw_value == shopBreadcrumbsSettingsModel::DB_TRUE;
			}

			return $raw_value;
		}
		else
		{
			$default_value = $this->settingsFieldsDefaultValues();

			return ifset($default_value[$field]);
		}
	}



	public function getRawSettings()
	{
		$settings_raw = self::$settings_raw;

		$default_values = $this->settingsFieldsDefaultValues();
		$bool_fields = $this->booleanSettingsFields();
		foreach ($default_values as $field => $value)
		{
			if (!isset($settings_raw[$field]))
			{
				$settings_raw[$field] = isset($bool_fields[$field])
					? ($value ? shopBreadcrumbsSettingsModel::DB_TRUE : shopBreadcrumbsSettingsModel::DB_FALSE)
					: $value;
			}
		}

		foreach ($bool_fields as $bool_field)
		{
			$settings_raw[$bool_field] = $settings_raw[$bool_field] == shopBreadcrumbsSettingsModel::DB_TRUE;
		}

		return $settings_raw;
	}
}