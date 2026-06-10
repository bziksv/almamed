<?php


class shopSeoPluginSettingsFetchProductController extends waJsonController
{
	private $product_settings_service;
	private $product_field_value_service;
	private $field_array_mapper;
	private $settings_array_mapper;
	private $fields_values_array_mapper;
	
	public function __construct()
	{
		$this->product_settings_service = shopSeoContext::getInstance()->getProductSettingsService();
		$this->product_field_value_service = shopSeoContext::getInstance()->getProductFieldValueService();
		$this->field_array_mapper = shopSeoContext::getInstance()->getFieldArrayMapper();
		$this->settings_array_mapper = shopSeoContext::getInstance()->getSettingsArrayMapper();
		$this->fields_values_array_mapper = shopSeoContext::getInstance()->getFieldsValuesArrayMapper();
	}
	
	public function execute()
	{
		$group_storefront_id = waRequest::request('group_storefront_id');
		$product_id = waRequest::request('product_id');
		$fields_json = waRequest::request('fields');
		$fields_array = json_decode($fields_json, true);
		$fields = $this->field_array_mapper->mapArrays($fields_array);
		
		$this->response = array(
			'settings' => $this->getSettings(
				$group_storefront_id,
				$product_id
			),
			'fields_values' => $this->getFieldsValues(
				$group_storefront_id,
				$product_id,
				$fields
			)
		);
	}
	
	private function getSettings($group_storefront_id, $product_id)
	{
		$settings = $this->product_settings_service->getByGroupStorefrontIdAndProductId(
			$group_storefront_id,
			$product_id
		);
		
		return $this->settings_array_mapper->mapSettings($settings);
	}
	
	/**
	 * @param $group_storefront_id
	 * @param $product_id
	 * @param shopSeoField[] $fields
	 * @return array
	 */
	private function getFieldsValues($group_storefront_id, $product_id, $fields)
	{
		$fields_values = $this->product_field_value_service->getByGroupStorefrontIdAndProductIdAndFields(
			$group_storefront_id,
			$product_id,
			$fields
		);
		
		return $this->fields_values_array_mapper->mapFieldsValues($fields_values);
	}
}