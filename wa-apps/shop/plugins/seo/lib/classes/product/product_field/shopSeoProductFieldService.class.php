<?php


class shopSeoProductFieldService extends shopSeoFieldService
{
	private $field_value_service;
	
	public function __construct(
		shopSeoProductFieldSource $field_source,
		shopSeoProductFieldsValuesService $product_field_value_service
	) {
		parent::__construct($field_source);
		$this->field_value_service = $product_field_value_service;
	}
	
	public function delete(shopSeoField $field)
	{
		parent::delete($field);
		
		if (!$field->getId())
		{
			return;
		}
		
		$this->field_value_service->deleteByFieldId($field->getId());
	}
}