<?php


interface shopSeoGroupCategoryStorefrontSource
{
	public function getByGroupId($id);
	
	public function updateByGroupId($id, $rows);
	
	public function deleteByGroupId($id);
}