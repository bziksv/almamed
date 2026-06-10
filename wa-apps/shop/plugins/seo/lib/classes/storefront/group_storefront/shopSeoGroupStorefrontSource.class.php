<?php


interface shopSeoGroupStorefrontSource
{
	public function getByGroupId($id);
	
	public function getAllGroups();
	
	public function getByStorefront($storefront);
	
	/**
	 * @param $row
	 * @return int
	 */
	public function addGroup($row);
	
	public function updateGroup($id, $row);
	
	public function updateSort();
	
	public function deleteGroup($id);
}