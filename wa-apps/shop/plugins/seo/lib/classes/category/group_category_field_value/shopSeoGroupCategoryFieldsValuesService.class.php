<?php


class shopSeoGroupCategoryFieldsValuesService
{
	private $fields_values_source;
	
	public function __construct(shopSeoGroupCategoryFieldsValuesSource $field_value_source)
	{
		$this->fields_values_source = $field_value_source;
	}
	
	/**
	 * @param $group_id
	 * @param shopSeoField[] $fields
	 * @return shopSeoGroupCategoryFieldsValues
	 */
	public function getByGroupIdAndFields($group_id, $fields)
	{
		$rows = $this->fields_values_source->getByGroupId($group_id);
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
		
		$field_value = new shopSeoGroupCategoryFieldsValues();
		$field_value->setGroupId($group_id);
		$field_value->setFields($fields);
		$field_value->setValues($values);
		
		return $field_value;
	}
	
	public function store(shopSeoGroupCategoryFieldsValues $fields_values)
	{
		$values = $fields_values->getValues();
		$rows = array();
		
		foreach ($fields_values->getFields() as $i => $field)
		{
			$rows[] = array(
				'group_id' => $fields_values->getGroupId(),
				'field_id' => $field->getId(),
				'value' => $values[$i],
			);
		}
		
		$this->fields_values_source->updateByGroupId($fields_values->getGroupId(), $rows);
	}
	
	public function deleteByFieldId($field_id)
	{
		$this->fields_values_source->deleteByFieldId($field_id);
	}
	
	public function deleteByGroupId($group_id)
	{
		$this->fields_values_source->deleteByGroupId($group_id);
	}
}