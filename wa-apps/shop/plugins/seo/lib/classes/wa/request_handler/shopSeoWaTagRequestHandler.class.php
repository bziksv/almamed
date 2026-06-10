<?php


class shopSeoWaTagRequestHandler implements shopSeoRequestHandler
{
	private $storefront;
	private $tag_name;
	private $page;
	private $collector;
	private $renderer;
	private $response;
	private $data;
	private $sort;
	private $direction;
	
	public function __construct($storefront, $tag_name, $page, $sort, $direction)
	{
		$this->storefront = $storefront;
		$this->tag_name = $tag_name;
		$this->page = $page;
		$this->sort = $sort;
		$this->direction = $direction;
		$this->collector = shopSeoContext::getInstance()->getTagDataCollector();
		$this->renderer = shopSeoContext::getInstance()->getTagDataRenderer();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}
	
	public function getType()
	{
		return 'tag';
	}
	
	public function applyInner()
	{
		$this->loadData();
		
		wa()->getView()->assign('tag_description', $this->data['description']);
	}
	
	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['meta_title']);
		$this->response->setMetaKeywords($this->data['meta_keywords']);
		$this->response->setMetaDescription($this->data['meta_description']);
		
		$this->response->appendSort($this->sort, $this->direction);
		$this->response->appendPagination($this->page);
	}
	
	private function loadData()
	{
		$data = $this->collector->collect($this->storefront, $info);
		$data = $this->renderer->renderAll($this->storefront, $this->tag_name, $this->page, $data);
		
		$this->data = $data;
	}
}