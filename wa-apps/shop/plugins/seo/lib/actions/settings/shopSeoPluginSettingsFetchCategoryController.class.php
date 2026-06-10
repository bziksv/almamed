<?php


class shopSeoPluginSettingsFetchCategoryController extends waJsonController
{
	private $category_settings_service;
	private $category_field_value_service;
	private $field_array_mapper;
	private $settings_array_mapper;
	private $fields_values_array_mapper;
	
	public function __construct()
	{
		$this->category_settings_service = shopSeoContext::getInstance()->getCategorySettingsService();
		$this->category_field_value_service = shopSeoContext::getInstance()->getCategoryFieldValueService();
		$this->field_array_mapper = shopSeoContext::getInstance()->getFieldArrayMapper();
		$this->settings_array_mapper = shopSeoContext::getInstance()->getSettingsArrayMapper();
		$this->fields_values_array_mapper = shopSeoContext::getInstance()->getFieldsValuesArrayMapper();
	}
	
	public function execute()
	{
		$group_storefront_id = waRequest::request('group_storefront_id');
		$category_id = waRequest::request('category_id');
		$fields_json = waRequest::request('fields');
		$fields_array = json_decode($fields_json, true);
		$fields = $this->field_array_mapper->mapArrays($fields_array);
		
		$this->response = array(
			'settings' => $this->getSettings(
				$group_storefront_id,
				$category_id
			),
			'fields_values' => $this->getFieldsValues(
				$group_storefront_id,
				$category_id,
				$fields
			)
		);
	}
	
	private function getSettings($group_storefront_id, $category_id)
	{
		$settings = $this->category_settings_service->getByGroupStorefrontIdAndCategoryId(
			$group_storefront_id,
			$category_id
		);
		
		return $this->settings_array_mapper->mapSettings($settings);
	}
	
	/**
	 * @param $group_storefront_id
	 * @param $category_id
	 * @param shopSeoField[] $fields
	 * @return array
	 */
	private function getFieldsValues($group_storefront_id, $category_id, $fields)
	{
		$fields_values = $this->category_field_value_service->getByGroupStorefrontIdAndCategoryIdAndFields(
			$group_storefront_id,
			$category_id,
			$fields
		);
		
		return $this->fields_values_array_mapper->mapFieldsValues($fields_values);
	}
}