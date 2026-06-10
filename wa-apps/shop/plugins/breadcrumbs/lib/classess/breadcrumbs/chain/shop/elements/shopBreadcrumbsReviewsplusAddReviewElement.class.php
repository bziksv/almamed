<?php

class shopBreadcrumbsReviewsplusAddReviewElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $title;

	/**
	 * @param string
	 */
	public function __construct($title)
	{
		$this->title = $title;
	}

	protected function initializeItems()
	{
		$route_params = array(
			'plugin' => shopBreadcrumbsShopBreadcrumbs::PLUGIN_REVIEWSPLUS,
			'module' => 'frontend',
			'action' => shopBreadcrumbsShopBreadcrumbs::ACTION_REVIEWSPLUS_ADD_REVIEW_PAGE,
		);

		$item = array(
			'name' => $this->title ? $this->title : 'Добавить отзыв',
			'url' => wa()->getRouteUrl('shop', $route_params),
		);

		return array($item);
	}
}