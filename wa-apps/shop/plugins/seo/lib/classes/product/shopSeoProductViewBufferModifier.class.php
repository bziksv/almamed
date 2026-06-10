<?php


class shopSeoProductViewBufferModifier
{
	private $product_data_source;
	private $category_data_source;
	private $category_inner_extender;
	private $product_inner_extender;
	
	public function __construct(
		shopSeoProductDataSource $product_data_source,
		shopSeoCategoryDataSource $category_data_source,
		shopSeoCategoryInnerExtender $category_inner_extender,
		shopSeoProductInnerExtender $product_inner_extender
	)
	{
		$this->product_data_source = $product_data_source;
		$this->category_data_source = $category_data_source;
		$this->category_inner_extender = $category_inner_extender;
		$this->product_inner_extender = $product_inner_extender;
	}
	
	public function modify($storefront, $product_id, shopSeoViewBuffer $view_buffer)
	{
		$vars['product'] = $this->product_inner_extender->extend(
			$storefront,
			$this->product_data_source->getProductData($product_id)
		);
		$category_id = $this->product_data_source->getProductCategoryId($product_id);
		
		if ($category_id)
		{
			$category = $this->category_data_source->getCategoryData($category_id);
			$categories = array_reverse(array_merge(
				array($category),
				$this->category_data_source->getCategoryPath($category_id)
			));
		}
		else
		{
			$categories = array();
		}
		
		foreach ($categories as $i => $category)
		{
			$categories[$i] = $this->category_inner_extender->extend($storefront, $category, false);
		}
		
		$vars['categories'] = $categories;
		$vars['root_category'] = reset($vars['categories']);
		$vars['category'] = end($vars['categories']);
		$vars['categories_names'] = array();
		
		foreach ($vars['categories'] as $i => $category)
		{
			$vars['categories_names'][$i] = $category['name'];
		}
		
		$vars['name'] = $vars['product']['name'];
		$vars['summary'] = $vars['product']['summary'];
		$vars['price'] = shop_currency_html($vars['product']['price'], null, null, true);
		
		$view_buffer->assign($vars);
	}
}