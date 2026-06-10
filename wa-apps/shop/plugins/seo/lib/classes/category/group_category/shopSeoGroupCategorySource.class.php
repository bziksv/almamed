<?php


interface shopSeoGroupCategorySource
{
	public function getByGroupId($id);
	
	public function getAllGroups();
	
	public function getByStorefrontAndCategoryId($storefront, $category_id);
	
	/**
	 * @param $row
	 * @return int
	 */
	public function addGroup($row);
	
	public function updateGroup($id, $row);
	
	public function updateSort();
	
	public function deleteGroup($id);
}