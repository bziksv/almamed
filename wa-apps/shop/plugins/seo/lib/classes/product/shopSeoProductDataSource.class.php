<?php


interface shopSeoProductDataSource
{
	public function getProductIds();
	
	public function getProductData($product_id);
	
	public function getProductCategoryId($product_id);
	
	public function getProductPage($page_id);
}