<?php


class shopSeoPluginSettingsFetchGroupCategoryController extends waJsonController
{
	private $group_category_service;
	private $group_category_array_mapper;
	private $field_array_mapper;
	
	public function __construct()
	{
		$this->group_category_service = shopSeoContext::getInstance()->getGroupCategoryService();
		$this->group_category_array_mapper = shopSeoContext::getInstance()->getGroupCategoryArrayMapper();
		$this->field_array_mapper = shopSeoContext::getInstance()->getFieldArrayMapper();
	}
	
	public function execute()
	{
		$group_id = waRequest::request('group_id');
		
		if ($group_id === '')
		{
			$group_id = null;
		}
		
		$fields_json = waRequest::request('fields');
		$fields_array = json_decode($fields_json, true);
		$fields = $this->field_array_mapper->mapArrays($fields_array);
		
		if (is_null($group_id))
		{
			$group_category = new shopSeoGroupCategory();
		}
		else
		{
			$group_category = $this->group_category_service->getById($group_id);
		}
		
		if (is_null($group_category))
		{
			$this->errors[] = 'Wrong group id';
			return;
		}
		
		$this->group_category_service->loadRules($group_category);
		$this->group_category_service->loadSettings($group_category);
		$this->group_category_service->loadFieldsValues($group_category, $fields);
		
		$this->response = $this->group_category_array_mapper->mapGroupCategory($group_category);
	}
}