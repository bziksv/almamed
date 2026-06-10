<?php


class shopSeoBrandExtender
{
	private $collector;
	private $renderer;
	
	public function __construct(shopSeoBrandDataCollector $collector, shopSeoBrandDataRenderer $renderer)
	{
		$this->collector = $collector;
		$this->renderer = $renderer;
	}
	
	public function extend($storefront, $brand, $page)
	{
		$brand_data = $this->collector->collect($storefront, $brand['id'], $info);
		$brand_data = $this->renderer->renderAll($storefront, $page, $brand_data);
		
		$brand['title'] = $brand_data['h1'];
		$brand['meta_title'] = $brand_data['meta_title'];
		$brand['meta_keywords'] = $brand_data['meta_keywords'];
		$brand['meta_description'] = $brand_data['meta_description'];
		$brand['original_description'] = $brand['description'];
		$brand['description'] = $brand_data['description'];
		
		return $brand;
	}
}