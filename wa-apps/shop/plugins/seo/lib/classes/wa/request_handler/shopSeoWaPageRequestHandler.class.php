<?php


class shopSeoWaPageRequestHandler implements shopSeoRequestHandler
{
	private $storefront;
	private $page_id;
	private $collector;
	private $renderer;
	private $response;
	private $data;
	
	public function __construct($storefront, $page_id)
	{
		$this->storefront = $storefront;
		$this->page_id = $page_id;
		$this->collector = shopSeoContext::getInstance()->getPageDataCollector();
		$this->renderer = shopSeoContext::getInstance()->getStorefrontDataRenderer();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'page';
	}
	
	public function applyInner()
	{
	
	}
	
	public function applyOuter()
	{
		$this->loadData();
		
		$this->response->setMetaTitle($this->data['meta_title']);
		$this->response->setMetaKeywords($this->data['meta_keywords']);
		$this->response->setMetaDescription($this->data['meta_description']);
	}
	
	private function loadData()
	{
		$data = $this->collector->collect($this->storefront, $this->page_id, $info);
		$data = $this->renderer->renderAll($this->storefront, $data);
		
		$this->data = $data;
	}
}