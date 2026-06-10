<?php


class shopSeoWaProductReviewRequestHandler implements shopSeoRequestHandler
{
	private $product_id;
	private $storefront;
	private $product_data_source;
	private $collector;
	private $renderer;
	private $extender;
	private $response;
	private $data;
	
	public function __construct($storefront, $product_id)
	{
		$this->storefront = $storefront;
		$this->product_id = $product_id;
		$this->product_data_source = shopSeoContext::getInstance()->getProductDataSource();
		$this->extender = shopSeoContext::getInstance()->getProductExtender();
		$this->collector = shopSeoContext::getInstance()->getProductReviewDataCollector();
		$this->renderer = shopSeoContext::getInstance()->getProductDataRenderer();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'product_review';
	}
	
	public function applyInner()
	{
		$this->loadData();
		
		wa()->getView()->assign('product', $this->data['product']);
	}
	
	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['review']['meta_title']);
		$this->response->setMetaKeywords($this->data['review']['meta_keywords']);
		$this->response->setMetaDescription($this->data['review']['meta_description']);
	}
	
	private function loadData()
	{
		$product = $this->extender->extend(
			$this->storefront,
			$this->product_data_source->getProductData($this->product_id)
		);
		
		$review_data = $this->collector->collect($this->storefront, $this->product_id, $info);
		$review_data = $this->renderer->renderAll($this->storefront, $this->product_id, $review_data);
		
		$this->data = array(
			'product' => $product,
			'review' => $review_data,
		);
	}
}