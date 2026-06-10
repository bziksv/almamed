<?php


class shopSeoWaBrandRequestHandler implements shopSeoRequestHandler
{
	private $storefront;
	private $brand_id;
	private $page;
	private $brand_data_source;
	private $extender;
	private $response;
	private $data;
	private $sort;
	private $direction;
	
	public function __construct($storefront, $brand_id, $page, $sort, $direction)
	{
		$this->storefront = $storefront;
		$this->brand_id = $brand_id;
		$this->page = $page;
		$this->sort = $sort;
		$this->direction = $direction;
		$this->brand_data_source = shopSeoContext::getInstance()->getBrandDataSource();
		$this->extender = shopSeoContext::getInstance()->getBrandExtender();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'brand';
	}
	
	public function applyInner()
	{
		$this->loadData();
		
		/** @var array $brand */
		$title = wa()->getView()->getVars('title');
		$vars = array();
		
		$vars['original_title'] = $title;
		$vars['title'] = $this->data['brand']['title'];
		$vars['brand'] = $this->data['brand'];
		
		wa()->getView()->assign($vars);
	}
	
	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['brand']['meta_title']);
		$this->response->setMetaKeywords($this->data['brand']['meta_keywords']);
		$this->response->setMetaDescription($this->data['brand']['meta_description']);
		
		$this->response->appendSort($this->sort, $this->direction);
		$this->response->appendPagination($this->page);
	}
	
	private function loadData()
	{
		$brand = $this->extender->extend(
			$this->storefront,
			$this->brand_data_source->getBrandData($this->brand_id),
			$this->page
		);
		
		$this->data = array(
			'brand' => $brand,
		);
	}
}