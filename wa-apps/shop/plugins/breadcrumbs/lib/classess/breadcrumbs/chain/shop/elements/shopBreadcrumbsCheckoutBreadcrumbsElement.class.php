<?php

class shopBreadcrumbsCheckoutBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	protected function initializeItems()
	{
		return array(
			array(
				'name' => 'Оформление заказа',
				'url' => wa()->getRouting()->getCurrentUrl(),
			)
		);
	}
}