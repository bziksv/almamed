<?php


interface shopSeoCategoryDataSource
{
	public function getCategoryIds();

	public function getCategoryData($category_id);

	public function updateByCategoryId($category_id, $row);

	public function getCategoryPath($category_id);

	public function getCategoryProductsData($storefront, $category_id);

	public function isCategoryStatic($category_id);

	public function getCategoryParams($category_id);
}