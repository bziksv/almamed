<?php


interface shopSeoGroupCategorySettingsSource
{
	public function getByGroupId($group_id);
	
	public function updateByGroupId($group_id, $rows);
	
	public function deleteByGroupId($group_id);
}