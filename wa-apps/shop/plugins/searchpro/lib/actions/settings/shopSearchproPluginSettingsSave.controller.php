<?php

class shopSearchproPluginSettingsSaveController extends waJsonController
{
	protected static $settings_model;
	protected static $storefront_groups_model;

	public function preExecute()
	{
		self::$settings_model = new shopSearchproSettingsModel();
		self::$storefront_groups_model = new shopSearchproStorefrontGroupsModel();
	}

	public function execute()
	{
		$basic_settings_json = waRequest::post('basic_settings');
		$basic_settings = json_decode($basic_settings_json, true);

		$storefronts_settings_json = waRequest::post('storefronts_settings');
		$storefronts_settings = json_decode($storefronts_settings_json, true);

		$themes_settings_json = waRequest::post('themes_settings');
		$themes_settings = json_decode($themes_settings_json, true);
		if(array_key_exists('*', $themes_settings))
			unset($themes_settings['*']);

		$templates_json = waRequest::post('templates');
		if($templates_json) {
			$templates = json_decode($templates_json, true);
		}

		$storefront_groups_json = waRequest::post('storefront_groups');
		$storefront_groups = json_decode($storefront_groups_json, true);

		if(isset($storefront_groups)) {
			self::$storefront_groups_model->set($storefront_groups);
		} else {
			self::$storefront_groups_model->truncate();
		}

		if(!$basic_settings || !$storefronts_settings) {
			$this->setError('Настройки не найдены, перезагрузите страницу');
		}

		if(isset($templates)) {
			if(array_key_exists('*', $templates))
				unset($templates['*']);

			$templates_instance = new shopSearchproTemplates();
			$templates_instance->set($templates);
		}

		$settings = array(
			'basic' => $basic_settings,
			'storefronts' => $storefronts_settings,
			'themes' => $themes_settings
		);
		self::$settings_model->set($settings);

		$stylesheet_instance = new shopSearchproStylesheet($themes_settings);
		$stylesheet_instance->render();
	}
}
