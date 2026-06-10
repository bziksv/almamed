<?php

class shopBreadcrumbsThemeSettingsModel extends waModel
{
	const USE_UTF8MB4_SETTING_NAME = 'use_theme_settings_utf8mb4';

	private static $use_utf8mb4 = null;

	protected $table = 'shop_breadcrumbs_theme_settings';

	public function __construct($type = null, $writable = false)
	{
		parent::__construct($type, $writable);

		if (self::$use_utf8mb4 === null)
		{
			$settings_model = new waAppSettingsModel();
			self::$use_utf8mb4 = $settings_model->get('shop.breadcrumbs', self::USE_UTF8MB4_SETTING_NAME, 0) == '1';
		}

		if (self::$use_utf8mb4)
		{
			$settings = shopBreadcrumbsWaDbConnector::getConfig($this->type);
			$settings['charset'] = 'utf8mb4';

			$this->adapter = waDbConnector::getConnection($settings, $this->writable);
		}
	}

	public function set($app, $theme_id, $setting, $value)
	{
		$data = array(
			'app' => $app,
			'theme_id' => $theme_id,
			'name' => $setting,
			'value' => $value,
		);

		$this->insert($data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}
}