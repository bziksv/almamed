<?php

class shopSearchproDetectorGotoAction extends shopSearchproDetectorAction
{
	/**
	 * @return string
	 */
	protected function getType()
	{
		return $this->getParam('type');
	}

	public function execute()
	{
		$type = $this->getType();

		$method = "getUrl_{$type}";

		if(method_exists($this, $method)) {
			$url = $this->$method();

			if($url) {
				wa()->getResponse()->redirect($url, 302);
			}
		}
	}

	private function getUrl_product()
	{
		$product = $this->getParam('product');

		$url = wa()->getRouteUrl('shop/frontend/product', array(
			'product_url' => $product['url']
		), true);

		return $url;
	}

	private function getUrl_category()
	{
		$category = $this->getParam('category');
		$category_url = $category['full_url'];
		if(waRequest::param('url_type') === '1') {
			$category_url = $category['url'];
		}

		$url = wa()->getRouteUrl('shop/frontend/category', array(
			'category_url' => $category_url
		), true);

		return $url;
	}

	private function getUrl_brand()
	{
		$brand = $this->getParam('brand_brand');
		if ($brand)
		{
			$route_params = array(
				'plugin' => 'brand',
				'module' => 'frontend',
				'action' => 'brandPage',
				'brand' => $brand['url'],
			);

			return wa()->getRouteUrl('shop', $route_params, true);
		}

		$brand = $this->getParam('brand_productbrands');
		if ($brand)
		{
			$brand_url = $brand['url'] ? $brand['url'] : urlencode($brand['name']);

			return wa()->getRouteUrl('shop/frontend/brand', array('brand' => $brand_url));
		}

		return null;
	}
}
