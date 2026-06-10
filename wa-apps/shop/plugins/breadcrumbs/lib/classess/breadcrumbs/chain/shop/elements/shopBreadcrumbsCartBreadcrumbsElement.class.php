<?php

class shopBreadcrumbsCartBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	protected function initializeItems()
	{
		return array(
			array(
				'name' => 'Корзина',
				'url' => wa()->getRouteUrl('shop/frontend/cart'),
			)
		);
	}
}