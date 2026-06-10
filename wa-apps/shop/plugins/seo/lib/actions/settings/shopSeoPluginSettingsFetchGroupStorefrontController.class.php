<?php


class shopSeoPluginSettingsFetchGroupStorefrontController extends waJsonController
{
	private $group_storefront_service;
	private $group_storefront_array_mapper;
	private $field_array_mapper;
	
	public function __construct()
	{
		$this->group_storefront_service = shopSeoContext::getInstance()->getGroupStorefrontService();
		$this->group_storefront_array_mapper = shopSeoContext::getInstance()->getGroupStorefrontArrayMapper();
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
		
		if (!is_null($group_id))
		{
			$group_storefront = $this->group_storefront_service->getById($group_id);
		}
		else
		{
			$group_storefront = new shopSeoGroupStorefront();
		}
		
		$this->group_storefront_service->loadRule($group_storefront);
		$this->group_storefront_service->loadSettings($group_storefront);
		$this->group_storefront_service->loadFieldsValues($group_storefront, $fields);
		
		if (is_null($group_storefront))
		{
			$this->errors[] = 'Wrong group id';
			return;
		}
		
		$this->response = $this->group_storefront_array_mapper->mapGroupStorefront($group_storefront);
	}
}