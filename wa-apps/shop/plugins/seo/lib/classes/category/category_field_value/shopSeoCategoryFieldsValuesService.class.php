<?php


class shopSeoCategoryFieldsValuesService
{
	private $fields_values_source;
	
	public function __construct(shopSeoCategoryFieldsValuesSource $fields_values_source)
	{
		$this->fields_values_source = $fields_values_source;
	}
	
	/**
	 * @param $group_storefront_id
	 * @param $category_id
	 * @param shopSeoField[] $fields
	 * @return shopSeoCategoryFieldsValues
	 */
	public function getByGroupStorefrontIdAndCategoryIdAndFields($group_storefront_id, $category_id, $fields)
	{
		$rows = $this->fields_values_source->getByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id);
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
		
		$field_value = new shopSeoCategoryFieldsValues();
		$field_value->setGroupStorefrontId($group_storefront_id);
		$field_value->setCategoryId($category_id);
		$field_value->setFields($fields);
		$field_value->setValues($values);
		
		return $field_value;
	}
	
	/**
	 * @param $category_id
	 * @param shopSeofield[] $fields
	 * @return shopSeoCategoryFieldsValues
	 */
	public function getGeneralByCategoryIdAndFields($category_id, $fields)
	{
		return $this->getByGroupStorefrontIdAndCategoryIdAndFields(0, $category_id, $fields);
	}
	
	public function store(shopSeoCategoryFieldsValues $fields_values)
	{
		$values = $fields_values->getValues();
		$rows = array();
		
		foreach ($fields_values->getFields() as $i => $field)
		{
			$rows[] = array(
				'group_storefront_id' => $fields_values->getGroupStorefrontId(),
				'category_id' => $fields_values->getCategoryId(),
				'field_id' => $field->getId(),
				'value' => $values[$i],
			);
		}
		
		$this->fields_values_source->updateByGroupStorefrontIdAndCategoryId(
			$fields_values->getGroupStorefrontId(),
			$fields_values->getCategoryId(),
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
	
	public function deleteByCategoryId($category_id)
	{
		$this->fields_values_source->deleteByCategoryId($category_id);
	}
}