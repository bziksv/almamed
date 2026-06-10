<?php

class shopSearchproProduct
{
	public $product_id;
	protected $data;
	private $shop_product_model;

	public static function create($product_id)
	{
		return new self($product_id);
	}

	private function __construct($product_id)
	{
		$this->product_id = $product_id;
		$this->shop_product_model = new shopProductModel();
		
		$this->init();
	}

	private function getShopProductModel()
	{
		return $this->shop_product_model;
	}

	private function init()
	{
		$this->data = $this->getShopProductModel()->getById($this->product_id);
	}

	public function get($field)
	{
		if(array_key_exists($field, $this->data)) {
			return $this->data[$field];
		}

		return null;
	}

	public function getAll()
	{
		return $this->data;
	}
}