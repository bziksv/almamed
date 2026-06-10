<?php

class shopBreadcrumbsProductReviewsBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $product;

	public function __construct($product)
	{
		$this->product = $product;
	}

	protected function initializeItems()
	{
		if (!$this->product)
		{
			return array();
		}

		$category_model = new shopCategoryModel();
		$category = $category_model->getById($this->product['category_id']);

		$category_url = $category
			? (waRequest::param('url_type') == 1 ? $category['url'] : $category['full_url'])
			: '';

		$route_params = array(
			'product_url' => $this->product['url'],
			'category_url' => $category_url
		);

		return array(
			array(
				'name' => 'Отзывы',
				'url' => wa()->getRouteUrl('shop/frontend/productReviews', $route_params),
			)
		);
	}
}