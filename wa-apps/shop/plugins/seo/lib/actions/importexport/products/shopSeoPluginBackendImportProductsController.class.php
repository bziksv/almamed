<?php


class shopSeoPluginBackendImportProductsController extends shopSeoCsvImportController
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
		
		$fields = $this->product_field_service->getFields();
		$this->data['group_storefront_id'] = waRequest::post('group_storefront_id');
		$this->data['fields'] = $fields;
		$this->data['offset'] = $this->offset();
		$this->data['size'] = $this->size();
	}
	
	protected function getMap()
	{
		$fields = $this->product_field_service->getFields();
		$source_map = array(
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
			$source_map["field:{$field->getId()}"] = "Поле №{$field->getId()}";
		}
		
		$header = $this->getHeader();
		$map = array();
		
		foreach ($header as $i => $column)
		{
			if (preg_match('/^Поле №\d+/u', $column, $matches))
			{
				$column = $matches[0];
			}
			
			$key = array_search($column, $source_map);
			
			if ($key)
			{
				$map[$key] = $i;
			}
		}
		
		return $map;
	}
	
	protected function isDone()
	{
		return $this->data['offset'] == $this->data['size'];
	}
	
	protected function step()
	{
		$data = $this->read();
		
		if (!is_null($data))
		{
			$this->handleData($data);
		}
		
		$this->data['offset'] = $this->offset();
		
		return true;
	}
	
	protected function getInfo()
	{
		return array(
			'processId' => $this->processId,
			'offset' => $this->data['offset'],
			'size' => $this->data['size'],
		);
	}
	
	private function handleData($data)
	{
		$product = new shopProduct($data['id']);
		$is_general = $this->data['group_storefront_id'] == 0;
		
		if (!$product->getId())
		{
			return;
		}
		
		if (isset($data['name']))
		{
			if ($product->name != $data['name'])
			{
				$product->name = $data['name'];
				$product->save();
			}
		}
		
		$settings = $this->product_settings_service->getByGroupStorefrontIdAndProductId(
			$this->data['group_storefront_id'],
			$product->id
		);
		
		if (isset($data['seo_name']))
		{
			$settings->seo_name = $data['seo_name'];
		}
		
		$meta_data = array();
		
		foreach (array('meta_title', 'meta_description', 'meta_keywords') as $name)
		{
			if (isset($data[$name]))
			{
				$meta_data[$name] = $data[$name];
			}
		}
		
		if ($is_general)
		{
			foreach ($meta_data as $name => $value)
			{
				$product->{$name} = $value;
			}
			
			$product->save();
		}
		else
		{
			foreach ($meta_data as $name => $value)
			{
				$settings->{$name} = $value;
			}
		}
		
		if (isset($data['h1']))
		{
			$settings->h1 = $data['h1'];
		}
		
		$this->product_settings_service->store($settings);
		$values = array();
		
		/** @var shopSeoField $field */
		foreach ($this->data['fields'] as $field)
		{
			if (isset($data['field'][$field->getId()]))
			{
				$values[] = $data['field'][$field->getId()];
			}
			else
			{
				$values[] = '';
			}
		}
		
		$fields_values = new shopSeoProductFieldsValues();
		$fields_values->setGroupStorefrontId($this->data['group_storefront_id']);
		$fields_values->setProductId($product['id']);
		$fields_values->setFields($this->data['fields']);
		$fields_values->setValues($values);
		$this->product_field_value_service->store($fields_values);
	}
}