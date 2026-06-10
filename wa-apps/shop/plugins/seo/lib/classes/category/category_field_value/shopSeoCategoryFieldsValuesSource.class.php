<?php


interface shopSeoCategoryFieldsValuesSource
{
	public function getByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id);
	
	public function updateByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id, $rows);
	
	public function deleteByGroupStorefrontId($group_storefront_id);
	
	public function deleteByCategoryId($category_id);
	
	public function deleteByFieldId($field_id);
}