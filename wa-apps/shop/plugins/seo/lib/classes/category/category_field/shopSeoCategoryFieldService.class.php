<?php


class shopSeoCategoryFieldService extends shopSeoFieldService
{
	private $field_value_service;
	private $group_field_value_service;
	
	public function __construct(
		shopSeoCategoryFieldSource $field_source,
		shopSeoCategoryFieldsValuesService $field_value_service,
		shopSeoGroupCategoryFieldsValuesService $group_category_field_value_service
	) {
		parent::__construct($field_source);
		$this->field_value_service = $field_value_service;
		$this->group_field_value_service = $group_category_field_value_service;
	}
	
	public function delete(shopSeoField $field)
	{
		parent::delete($field);
		
		if (!$field->getId())
		{
			return;
		}
		
		$this->field_value_service->deleteByFieldId($field->getId());
		$this->group_field_value_service->deleteByFieldId($field->getId());
	}
}