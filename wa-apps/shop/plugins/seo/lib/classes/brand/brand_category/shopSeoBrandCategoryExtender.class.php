<?php


class shopSeoBrandCategoryExtender
{
	private $brand_category_data_collector;
	private $brand_category_data_renderer;
	private $category_data_collector;
	private $env;
	
	public function __construct(
		shopSeoBrandCategoryDataCollector $brand_category_data_collector,
		shopSeoBrandCategoryDataRenderer $brand_category_data_renderer,
		shopSeoCategoryDataCollector $category_data_collector,
		shopSeoEnv $env
	)
	{
		$this->brand_category_data_collector = $brand_category_data_collector;
		$this->brand_category_data_renderer = $brand_category_data_renderer;
		$this->category_data_collector = $category_data_collector;
		$this->env = $env;
	}
	
	public function extend($storefront, $category, $page)
	{
		$category_data = $this->brand_category_data_collector->collect($storefront, $info);
		$category_data = $this->brand_category_data_renderer->renderAll($storefront, $category['id'], $page, $category_data);
		
		$seo_name = $this->category_data_collector->collectSeoName($storefront, $category['id'], $info);
		$fields = $this->category_data_collector->collectFieldsValues($storefront, $category['id'], $info);
		
		if ($seo_name === '')
		{
			$seo_name = $category['name'];
		}
		
		$category['seo_name'] = $seo_name;
		$category['fields'] = $fields;
		$category['original_name'] = $category['name'];
		
		if ($category_data['h1'] !== '')
		{
			$category['name'] = $category_data['h1'];
		}
		
		$category['meta_title'] = $category_data['meta_title'];
		$category['meta_keywords'] = $category_data['meta_keywords'];
		$category['meta_description'] = $category_data['meta_description'];
		$category['description'] = $category_data['description'];
		
		return $category;
	}
}