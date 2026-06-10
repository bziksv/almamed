<?php


interface shopSeoGroupCategoryFieldsValuesSource
{
	public function getByGroupId($group_id);
	
	public function updateByGroupId($group_id, $rows);
	
	public function deleteByFieldId($field_id);
	
	public function deleteByGroupId($group_id);
}