<?php

class shopBreadcrumbsSearchproBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	protected function initializeItems()
	{
		return array(
			array(
				'name' => 'Результаты поиска',
				'url' => wa()->getRouting()->getCurrentUrl(),
			)
		);
	}
}