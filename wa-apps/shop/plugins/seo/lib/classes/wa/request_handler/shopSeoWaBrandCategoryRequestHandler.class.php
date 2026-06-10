<?php


class shopSeoWaBrandCategoryRequestHandler implements shopSeoRequestHandler
{
	private $storefront;
	private $brand_id;
	private $category_id;
	private $page;
	private $sort;
	private $direction;
	private $category_data_source;
	private $brand_category_extender;
	private $brand_data_source;
	private $brand_extender;
	private $response;
	private $data;
	
	public function __construct($storefront, $brand_id, $category_id, $page, $sort, $direction)
	{
		$this->storefront = $storefront;
		$this->brand_id = $brand_id;
		$this->category_id = $category_id;
		$this->page = $page;
		$this->sort = $sort;
		$this->direction = $direction;
		$this->category_data_source = shopSeoContext::getInstance()->getCategoryDataSource();
		$this->brand_category_extender = shopSeoContext::getInstance()->getBrandCategoryExtender();
		$this->brand_data_source = shopSeoContext::getInstance()->getBrandDataSource();
		$this->brand_extender = shopSeoContext::getInstance()->getBrandExtender();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'brand_category';
	}
	
	public function applyInner()
	{
		$this->loadData();

		$brand = $this->data['brand'];
		$brand['description'] = $this->data['category']['description'];

		wa()->getView()->assign('brand', $brand);
		wa()->getView()->assign('title', $this->data['category']['name']);
	}
	
	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['category']['meta_title']);
		$this->response->setMetaKeywords($this->data['category']['meta_keywords']);
		$this->response->setMetaDescription($this->data['category']['meta_description']);
		
		$this->response->appendSort($this->sort, $this->direction);
		$this->response->appendPagination($this->page);
	}
	
	private function loadData()
	{
		$brand = $this->brand_extender->extend(
			$this->storefront,
			$this->brand_data_source->getBrandData($this->brand_id),
			$this->page
		);
		
		$category = $this->brand_category_extender->extend(
			$this->storefront,
			$this->category_data_source->getCategoryData($this->category_id),
			$this->page
		);
		
		$this->data = array(
			'brand' => $brand,
			'category' => $category,
		);
	}
}
