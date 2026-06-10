<?php


class shopSeoPluginBackendExportCategoriesController extends shopSeoCsvExportController
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
	
	/**
	 * Initializes new process.
	 * Runs inside a transaction ($this->data and $this->fd are accessible).
	 */
	protected function init()
	{
		parent::init();
		
		$fields = $this->category_field_service->getFields();
		
		$this->data['group_storefront_id'] = waRequest::get('group_storefront_id');
		$this->data['category_ids'] = $this->category_data_source->getCategoryIds();
		$this->data['fields'] = $fields;
		$this->data['offset'] = 0;
	}
	
	protected function getMap()
	{
		$fields = $this->category_field_service->getFields();
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
		return $this->data['offset'] == count($this->data['category_ids']);
	}
	
	protected function step()
	{
		$category_id = $this->data['category_ids'][$this->data['offset']];
		$data = $this->getData($category_id);
		
		$this->write($data);
		
		$this->data['offset']++;
		
		return true;
	}
	
	protected function getInfo()
	{
		return array(
			'processId' => $this->processId,
			'offset' => $this->data['offset'],
			'categories_count' => count($this->data['category_ids']),
		);
	}
	
	private function getData($category_id)
	{
		$category = $this->category_data_source->getCategoryData($category_id);
		$settings = $this->category_settings_service->getByGroupStorefrontIdAndCategoryId(
			$this->data['group_storefront_id'],
			$category_id
		);
		$is_general = $this->data['group_storefront_id'] == 0;
		$_fields_values = $this->category_field_value_service->getByGroupStorefrontIdAndCategoryIdAndFields(
			$this->data['group_storefront_id'],
			$category_id,
			$this->data['fields']
		);
		$fields_values = array();
		
		$values = $_fields_values->getValues();
		
		foreach ($_fields_values->getFields() as $i => $field)
		{
			$fields_values[$field->getId()] = $values[$i];
		}
		
		$name = str_repeat('- ', $category['depth']) . $category['name'];
		
		if ($category['depth'] > 0)
		{
			$name = "| {$name}";
		}
		
		return array(
			'id' => $category['id'],
			'name' => $name,
			'seo_name' => $settings->seo_name,
			'h1' => $settings->h1,
			'meta_title' => $is_general ? $category['meta_title'] : $settings->meta_title,
			'meta_description' => $is_general ? $category['meta_description'] : $settings->meta_description,
			'meta_keywords' => $is_general ? $category['meta_keywords'] : $settings->meta_keywords,
			'field' => $fields_values,
		);
	}
}