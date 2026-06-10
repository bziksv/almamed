<?php


class shopSeoProductFieldsValues implements shopSeoFieldsValues
{
	private $group_storefront_id;
	private $product_id;
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
	
	public function getProductId()
	{
		return $this->product_id;
	}
	
	public function setProductId($product_id)
	{
		$this->product_id = $product_id;
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