<?php


class shopSeoWaHomeRequestHandler implements shopSeoRequestHandler
{
	private $storefront;
	private $collector;
	private $renderer;
	private $response;
	private $data;
	
	public function __construct($storefront)
	{
		$this->storefront = $storefront;
		$this->collector = shopSeoContext::getInstance()->getHomeDataCollector();
		$this->renderer = shopSeoContext::getInstance()->getStorefrontDataRenderer();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'home';
	}
	
	public function applyInner()
	{
		$this->loadData();
		
		wa()->getView()->assign('home_page_description', $this->data['description']);
	}
	
	public function applyOuter()
	{
		if (empty($this->data)) {
			$this->loadData();
		}

		$this->response->setMetaTitle($this->data['meta_title']);
		$this->response->setMetaKeywords($this->data['meta_keywords']);
		$this->response->setMetaDescription($this->data['meta_description']);
	}
	
	private function loadData()
	{
		$data = $this->collector->collect($this->storefront, $info);
		$data = $this->renderer->renderAll($this->storefront, $data);
		
		$this->data = $data;
	}
}