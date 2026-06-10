<?php


class shopSeoPluginBackendImportCategoriesController extends shopSeoCsvImportController
{
	private $category_data_source;
	private $category_settings_service;
	private $category_field_service;
	private $category_field_value_service;
	
	public function __construct()
	{
		$this->category_data_source = shopSeoContext::getInstance()->getCategoryDataSource();
		$this->category_settings_service = shopSeoContext::getInstance()->getCategorySettingsService();
		$this->category_field_service = shopSeoContext::getInstance()->getCategoryFieldService();
		$this->category_field_value_service = shopSeoContext::getInstance()->getCategoryFieldValueService();
	}
	
	protected function init()
	{
		parent::init();
		$fields = $this->category_field_service->getFields();
		$this->data['group_storefront_id'] = waRequest::post('group_storefront_id');
		$this->data['fields'] = $fields;
		$this->data['offset'] = $this->offset();
		$this->data['size'] = $this->size();
	}
	
	protected function getMap()
	{
		$fields = $this->category_field_service->getFields();
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
			if (preg_match('/Поле №\d+/u', $column, $matches))
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
		$category = $this->category_data_source->getCategoryData($data['id']);
		$is_general = $this->data['group_storefront_id'] == 0;
		
		if (is_null($category))
		{
			return;
		}
		
		if (isset($data['name']))
		{
			$this->category_data_source->updateByCategoryId($category['id'], array(
				'name' => preg_replace('/^(?:\| (?:- )+)/', '', $data['name']),
			));
		}
		
		$settings = $this->category_settings_service->getByGroupStorefrontIdAndCategoryId(
			$this->data['group_storefront_id'],
			$category['id']
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
			$this->category_data_source->updateByCategoryId($category['id'], $meta_data);
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
		
		$this->category_settings_service->store($settings);
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
		
		$fields_values = new shopSeoCategoryFieldsValues();
		$fields_values->setGroupStorefrontId($this->data['group_storefront_id']);
		$fields_values->setCategoryId($category['id']);
		$fields_values->setFields($this->data['fields']);
		$fields_values->setValues($values);
		$this->category_field_value_service->store($fields_values);
	}
}