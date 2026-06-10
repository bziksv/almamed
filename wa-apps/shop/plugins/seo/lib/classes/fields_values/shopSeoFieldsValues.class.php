<?php


interface shopSeoFieldsValues
{
	/**
	 * @return shopSeoField[]
	 */
	public function getFields();
	
	public function setFields($fields);
	
	/**
	 * @return string[]
	 */
	public function getValues();
	
	public function setValues($values);
	
	public function deleteField(shopSeoField $field);
}