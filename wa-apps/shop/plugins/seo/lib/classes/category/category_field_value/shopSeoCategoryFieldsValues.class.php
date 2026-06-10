<?php


class shopSeoCategoryFieldsValues implements shopSeoFieldsValues
{
	private $group_storefront_id;
	private $category_id;
	/** @var shopSeoField[] */
	private $fields;
	/** @var string[] */
	private $values;
	
	public function getGroupStorefrontId()
	{
		return $this->group_storefront_id;
	}
	
	public function setGroupStorefrontId($group_storefront_id)
	{
		$this->group_storefront_id = $group_storefront_id;
	}
	
	public function getCategoryId()
	{
		return $this->category_id;
	}
	
	public function setCategoryId($category_id)
	{
		$this->category_id = $category_id;
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