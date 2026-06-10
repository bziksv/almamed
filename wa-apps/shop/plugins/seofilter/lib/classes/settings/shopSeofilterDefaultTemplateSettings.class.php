<?php

/**
 * Class shopSeofilterDefaultTemplateSettings
 * @property bool $pagination_is_enabled
 */
class shopSeofilterDefaultTemplateSettings extends shopSeofilterSettings
{
	protected function defaultSettings()
	{
		return array(
			'pagination_is_enabled' => self::DB_FALSE,
		);
	}

	protected function booleanSettingsFields()
	{
		return array(
			'pagination_is_enabled' => 1,
		);
	}
}