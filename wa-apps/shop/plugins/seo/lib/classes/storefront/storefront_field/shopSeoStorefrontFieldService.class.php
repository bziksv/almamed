<?php


class shopSeoStorefrontFieldService extends shopSeoFieldService
{
	private $field_value_service;
	
	public function __construct(
		shopSeoStorefrontFieldSource $field_source,
		shopSeoStorefrontFieldsValuesService $field_value_service
	) {
		parent::__construct($field_source);
		$this->field_value_service = $field_value_service;
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