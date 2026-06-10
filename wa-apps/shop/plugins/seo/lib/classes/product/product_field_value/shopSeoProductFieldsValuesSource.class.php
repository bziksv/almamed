<?php


interface shopSeoProductFieldsValuesSource
{
	public function getByGroupStorefrontIdAndProductId($group_storefront_id, $product_id);
	
	public function updateByGroupStorefrontIdAndProductId($group_storefront_id, $product_id, $rows);
	
	public function deleteByGroupStorefrontId($group_storefront_id);
	
	public function deleteByProductId($product_id);
	
	public function deleteByFieldId($field_id);
}