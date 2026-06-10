<?php


interface shopSeoFieldSource
{
	public function getFieldsRows();
	
	public function getFieldRow($field_id);
	
	public function addField($row);
	
	public function updateField($field_id, $row);
	
	public function deleteField($field_id);
}