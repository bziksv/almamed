<?php


class shopSeoWaProductPageRequestHandler implements shopSeoRequestHandler
{
	private $product_id;
	private $storefront;
	private $page_id;
	private $product_data_source;
	private $collector;
	private $renderer;
	private $extender;
	private $response;
	private $data;

	public function __construct($storefront, $product_id, $page_id)
	{
		$this->storefront = $storefront;
		$this->product_id = $product_id;
		$this->page_id = $page_id;
		$this->product_data_source = shopSeoContext::getInstance()->getProductDataSource();
		$this->extender = shopSeoContext::getInstance()->getProductExtender();
		$this->collector = shopSeoContext::getInstance()->getProductPageDataCollector();
		$this->renderer = shopSeoContext::getInstance()->getProductDataRenderer();
		$this->response = shopSeoContext::getInstance()->getResponse();
	}

	public function getType()
	{
		return 'product_page';
	}

	public function applyInner()
	{
		$this->loadData();

		/** @var array $page */
		$page = wa()->getView()->getVars('page');
		$page['original_title'] = $page['title'];
		$page['title'] = $this->data['page']['h1'];

		wa()->getView()->assign(array(
			'product' => $this->data['product'],
			'page' => $page,
		));
	}

	public function applyOuter()
	{
		$this->response->setMetaTitle($this->data['page']['meta_title']);
		$this->response->setMetaKeywords($this->data['page']['meta_keywords']);
		$this->response->setMetaDescription($this->data['page']['meta_description']);
	}

	private function loadData()
	{
		$product = $this->extender->extend(
			$this->storefront,
			$this->product_data_source->getProductData($this->product_id)
		);

		$page_data = $this->collector->collect($this->storefront, $this->product_id, $this->page_id, $info);
		$page_data = $this->renderer->renderAll($this->storefront, $this->product_id, $page_data);

		$this->data = array(
			'product' => $product,
			'page' => $page_data,
		);
	}
}
