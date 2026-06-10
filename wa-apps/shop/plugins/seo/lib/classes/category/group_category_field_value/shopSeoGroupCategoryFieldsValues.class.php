<?php


class shopSeoGroupCategoryFieldsValues implements shopSeoFieldsValues
{
	private $group_id;
	/** @var shopSeoField[] */
	private $fields;
	/** @var string[] */
	private $values;
	
	public function getGroupId()
	{
		return $this->group_id;
	}
	
	public function setGroupId($group_id)
	{
		$this->group_id = $group_id;
	}
	
	public function getFields()
	{
		return $this->fields;
	}
	
	public function setFields($fields)
	{
		$this->fields = $fields;
	}
	
	public function getValues()
	{
		return $this->values;
	}
	
	public function setValues($values)
	{
		$this->values = $values;
	}
	
	public function deleteField(shopSeoField $field)
	{
		$i = array_search($field, $this->fields);
		array_splice($this->fields, $i, 1);
		array_splice($this->values, $i, 1);
	}
}