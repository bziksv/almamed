<?php

class shopBreadcrumbsSearchBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	protected function initializeItems()
	{
		return array(
			array(
				'name' => 'Поиск',
				'url' => wa()->getRouting()->getCurrentUrl(),
			)
		);
	}
}