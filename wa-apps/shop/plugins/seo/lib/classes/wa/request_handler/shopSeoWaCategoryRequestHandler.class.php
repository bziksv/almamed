<?php


class shopSeoWaCategoryRequestHandler implements shopSeoRequestHandler
{
	private $category_id;
	private $storefront;
	private $page;
	private $sort;
	private $direction;
	private $category_data_source;
	private $extender;
	private $env;
	private $response;
	private $data;
	
	public function __construct($storefront, $category_id, $page, $sort, $direction)
	{
		$this->storefront = $storefront;
		$this->category_id = $category_id;
		$this->page = $page;
		$this->sort = $sort;
		$this->direction = $direction;
		$this->category_data_source = shopSeoContext::getInstance()->getCategoryDataSource();
		$this->extender = shopSeoContext::getInstance()->getCategoryExtender();
		$this->env = shopSeoContext::getInstance()->getEnv();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'category';
	}
	
	public function applyInner()
	{
		$this->loadData();
		
		wa()->getView()->assign($this->data);
	}
	
	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['category']['meta_title']);
		$this->response->setMetaKeywords($this->data['category']['meta_keywords']);
		$this->response->setMetaDescription($this->data['category']['meta_description']);
		
		if ($this->env->isSupportOg())
		{
			$this->response->setOgTitle($this->data['category']['og']['title']);
			$this->response->setOgDescription($this->data['category']['og']['description']);
		}
		
		$this->response->appendSort($this->sort, $this->direction);
		$this->response->appendPagination($this->page);
	}
	
	private function loadData()
	{
		$category = $this->extender->extend(
			$this->storefront,
			$this->category_data_source->getCategoryData($this->category_id),
			$this->page
		);
		
		$this->data = array(
			'category' => $category,
		);
	}
}