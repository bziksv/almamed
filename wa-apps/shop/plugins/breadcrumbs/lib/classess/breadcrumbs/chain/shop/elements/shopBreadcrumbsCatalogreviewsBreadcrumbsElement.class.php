<?php

class shopBreadcrumbsCatalogreviewsBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	protected function initializeItems()
	{
		return array(
			array(
				'name' => 'Отзывы',
				'url' => wa()->getRouting()->getCurrentUrl(),
			)
		);
	}
}