<?php

class shopBreadcrumbsProductPageBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $product;
	private $product_page;

	public function __construct($product, $product_page)
	{
		$this->product = $product;
		$this->product_page = $product_page;
	}

	protected function initializeItems()
	{
		if (!$this->product || !$this->product_page)
		{
			return array();
		}

		$seo_extender = new shopBreadcrumbsSeoExtender();
		$product_page = $seo_extender->extendProductPage($this->product, $this->product_page);

		$category_model = new shopCategoryModel();
		$category = $category_model->getById($this->product['category_id']);

		$category_url = $category
			? waRequest::param('url_type') == 1 ? $category['url'] : $category['full_url']
			: '';

		$route_params = array(
			'product_url' => $this->product['url'],
			'category_url' => $category_url,
			'page_url' => $product_page['url'],
		);

		return array(
			array(
				'name' => $product_page['title'],
				'url' => wa()->getRouteUrl('shop/frontend/productPage', $route_params)
			)
		);
	}
}