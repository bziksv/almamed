<?php


class shopSeoPluginBackendExportProductsController extends shopSeoCsvExportController
{
	private $product_data_source;
	private $product_settings_service;
	private $product_field_service;
	private $product_field_value_service;
	
	public function __construct()
	{
		$this->product_data_source = shopSeoContext::getInstance()->getProductDataSource();
		$this->product_settings_service = shopSeoContext::getInstance()->getProductSettingsService();
		$this->product_field_service = shopSeoContext::getInstance()->getProductFieldService();
		$this->product_field_value_service = shopSeoContext::getInstance()->getProductFieldValueService();
	}
	
	protected function init()
	{
		parent::init();
		
		$this->data['group_storefront_id'] = waRequest::get('group_storefront_id');
		$this->data['product_ids'] = $this->product_data_source->getProductIds();
		$fields = $this->product_field_service->getFields();
		$this->data['fields'] = $fields;
		$this->data['offset'] = 0;
	}
	
	protected function getMap()
	{
		$fields = $this->product_field_service->getFields();
		$map = array(
			'id' => 'Id',
			'name' => 'Название',
			'seo_name' => 'SEO-название',
			'h1' => 'H1 заголовок',
			'meta_title' => 'Title',
			'meta_description' => 'META Description',
			'meta_keywords' => 'META Keywords',
		);
		
		foreach ($fields as $field)
		{
			$map["field:{$field->getId()}"] = "Поле №{$field->getId()}: {$field->getName()}";
		}
		
		return $map;
	}
	
	protected function isDone()
	{
		return $this->data['offset'] == count($this->data['product_ids']);
	}
	
	protected function step()
	{
		$product_id = $this->data['product_ids'][$this->data['offset']];
		$data = $this->getData($product_id);
		
		$this->write($data);
		
		$this->data['offset']++;
		
		return true;
	}
	
	protected function getInfo()
	{
		return array(
			'processId' => $this->processId,
			'offset' => $this->data['offset'],
			'products_count' => count($this->data['product_ids']),
		);
	}
	
	private function getData($product_id)
	{
		$product = $this->product_data_source->getProductData($product_id);
		$settings = $this->product_settings_service->getByGroupStorefrontIdAndProductId(
			$this->data['group_storefront_id'],
			$product_id
		);
		$is_general = $this->data['group_storefront_id'] == 0;
		$_fields_values = $this->product_field_value_service->getByGroupStorefrontIdAndProductIdAndFields(
			$this->data['group_storefront_id'],
			$product_id,
			$this->data['fields']
		);
		$fields_values = array();
		
		$values = $_fields_values->getValues();
		
		foreach ($_fields_values->getFields() as $i => $field)
		{
			$fields_values[$field->getId()] = $values[$i];
		}
		
		/** @var shopSeoField $field */
		foreach ($this->data['fields'] as $field)
		{
			$fields_values[$field->getId()] = isset($fields_values[$field->getId()]) ? $fields_values[$field->getId()] : '';
		}
		
		return array(
			'id' => $product['id'],
			'name' => $product['name'],
			'seo_name' => $settings->seo_name,
			'h1' => $settings->h1,
			'meta_title' => $is_general ? $product['meta_title'] : $settings->meta_title,
			'meta_description' => $is_general ? $product['meta_description'] : $settings->meta_description,
			'meta_keywords' => $is_general ? $product['meta_keywords'] : $settings->meta_keywords,
			'field' => $fields_values,
		);
	}
}