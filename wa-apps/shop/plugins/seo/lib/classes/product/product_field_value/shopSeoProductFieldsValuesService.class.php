<?php


class shopSeoProductFieldsValuesService
{
	private $fields_values_source;
	
	public function __construct(shopSeoProductFieldsValuesSource $fields_values_source)
	{
		$this->fields_values_source = $fields_values_source;
	}
	
	/**
	 * @param $group_storefront_id
	 * @param $product_id
	 * @param shopSeoField[] $fields
	 * @return shopSeoProductFieldsValues
	 */
	public function getByGroupStorefrontIdAndProductIdAndFields($group_storefront_id, $product_id, $fields)
	{
		$rows = $this->fields_values_source->getByGroupStorefrontIdAndProductId($group_storefront_id, $product_id);
		$values_by_id = array();
		
		foreach ($rows as $row)
		{
			$values_by_id[$row['field_id']] = $row['value'];
		}
		
		$values = array();
		
		foreach ($fields as $field)
		{
			if (array_key_exists($field->getId(), $values_by_id))
			{
				$values[] = $values_by_id[$field->getId()];
			}
			else
			{
				$values[] = '';
			}
		}
		
		$field_value = new shopSeoProductFieldsValues();
		$field_value->setGroupStorefrontId($group_storefront_id);
		$field_value->setProductId($product_id);
		$field_value->setFields($fields);
		$field_value->setValues($values);
		
		return $field_value;
	}
	
	/**
	 * @param $product_id
	 * @param shopSeofield[] $fields
	 * @return shopSeoProductFieldsValues
	 */
	public function getGeneralByProductIdAndFields($product_id, $fields)
	{
		return $this->getByGroupStorefrontIdAndProductIdAndFields(0, $product_id, $fields);
	}
	
	public function store(shopSeoProductFieldsValues $fields_values)
	{
		$values = $fields_values->getValues();
		$rows = array();
		
		foreach ($fields_values->getFields() as $i => $field)
		{
			$rows[] = array(
				'group_storefront_id' => $fields_values->getGroupStorefrontId(),
				'product_id' => $fields_values->getProductId(),
				'field_id' => $field->getId(),
				'value' => $values[$i],
			);
		}
		
		$this->fields_values_source->updateByGroupStorefrontIdAndProductId(
			$fields_values->getGroupStorefrontId(),
			$fields_values->getProductId(),
			$rows
		);
	}
	
	public function deleteByFieldId($field_id)
	{
		$this->fields_values_source->deleteByFieldId($field_id);
	}
	
	public function deleteByGroupStorefrontId($group_storefront_id)
	{
		$this->fields_values_source->deleteByGroupStorefrontId($group_storefront_id);
	}
	
	public function deleteByProductId($product_id)
	{
		$this->fields_values_source->deleteByProductId($product_id);
	}
}