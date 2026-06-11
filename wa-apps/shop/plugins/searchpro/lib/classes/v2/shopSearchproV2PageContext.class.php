<?php

class shopSearchproV2PageContext
{
	public $query;
	public $category_id;
	public $products;
	public $is_empty;
	public $products_count;

	public function __construct($query, $category_id, shopSearchproResult $products, $is_empty = false)
	{
		$this->query = $query;
		$this->category_id = (int) $category_id;
		$this->products = $products;
		$this->is_empty = (bool) $is_empty;
		$this->products_count = $products->isEmpty() ? 0 : $products->getCount();
	}
}
