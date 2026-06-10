<?php


class shopSeoWaProductRequestHandler implements shopSeoRequestHandler
{
	private $product_id;
	private $storefront;
	private $product_data_source;
	private $extender;
	private $env;
	private $response;
	private $data;
	
	public function __construct($storefront, $product_id)
	{
		$this->storefront = $storefront;
		$this->product_id = $product_id;
		$this->product_data_source = shopSeoContext::getInstance()->getProductDataSource();
		$this->extender = shopSeoContext::getInstance()->getProductExtender();
		$this->env = shopSeoContext::getInstance()->getEnv();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'product';
	}
	
	public function applyInner()
	{
		$this->loadData();
		
		wa()->getView()->assign($this->data);
	}
	
	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['product']['meta_title']);
		$this->response->setMetaKeywords($this->data['product']['meta_keywords']);
		$this->response->setMetaDescription($this->data['product']['meta_description']);
		
		if ($this->env->isSupportOg())
		{
			$this->response->setOgTitle($this->data['product']['og']['title']);
			$this->response->setOgDescription($this->data['product']['og']['description']);
		}
	}
	
	private function loadData()
	{
		$product = $this->extender->extend(
			$this->storefront,
			$this->product_data_source->getProductData($this->product_id)
		);
		
		$this->data = array(
			'product' => $product,
		);
	}
}