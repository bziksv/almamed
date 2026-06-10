<?php

class shopBreadcrumbsCategoryBreadcrumbsElement extends shopBreadcrumbsBreadcrumbsElement
{
	private $current_category;
	private $category_name_mode;

	public function __construct($current_category, $category_name_mode)
	{
		$this->current_category = $current_category;
		$this->category_name_mode = $category_name_mode;
	}

	protected function initializeItems()
	{
		$categories = $this->getCategories();

		$items = array();
		foreach ($categories as $category)
		{
			$items[] = array(
				'id' => $category['id'],
				'name' => $category['name'],
				'url' => $category['frontend_url'],
				'brothers' => array_values($category['brothers']),
			);
		}

		return $items;
	}

	protected function isWithCategoryItemsBrothers()
	{
		return false;
	}

	private function getCategories()
	{
		if (!$this->current_category)
		{
			return array();
		}

		$path = $this->getCategoriesPath($this->current_category);
		if ($this->isPathContainsHiddenCategories($path))
		{
			return array();
		}

		$category_url_template = wa()->getRouteUrl('shop/frontend/category', array('category_url' => '%CATEGORY_URL%'));
		$url_field = waRequest::param('url_type') == '1' ? 'url' : 'full_url';

		foreach ($path as $index => $category)
		{
			$seo_extender = new shopBreadcrumbsSeoExtender();

			$path[$index] = $seo_extender->extendCategory($category, $this->isWithCategoryItemsBrothers(), $this->category_name_mode);
			$path[$index]['frontend_url'] = str_replace('%CATEGORY_URL%', $category[$url_field], $category_url_template);

			if (!isset($path[$index]['brothers']) || !is_array($path[$index]['brothers']))
			{
				$path[$index]['brothers'] = array();
			}

			foreach ($path[$index]['brothers'] as $brother_id => $brother)
			{
				$path[$index]['brothers'][$brother_id]['frontend_url'] = str_replace('%CATEGORY_URL%', $brother[$url_field], $category_url_template);
			}

			unset($path[$index]['brothers'][$this->current_category['id']]);
			unset($path[$index]['brothers'][$category['id']]);
		}

		return $path;
	}

	private function getCategoriesPath($current_category)
	{
		$model = new shopCategoryModel();
		$path = array($current_category);

		$category_id = $current_category['parent_id'];
		while ($category_id > 0)
		{
			$category = $model->getById($category_id);
			if (!$category)
			{
				break;
			}

			$path[] = $category;
			$category_id = $category['parent_id'];
		}

		return array_reverse($path);
	}

	private function isPathContainsHiddenCategories($path)
	{
		$category_ids = array();
		foreach ($path as $category)
		{
			$category_ids[] = $category['id'];
		}


		$storefront = shopBreadcrumbsPlugin::getStorefront();

		$category_routes_model = new shopCategoryRoutesModel();
		$routes = $category_routes_model->getRoutes($category_ids);

		foreach ($path as $c)
		{
			if (isset($routes[$c['id']]) && !in_array($storefront, $routes[$c['id']]))
			{
				return true;
			}
		}

		return false;
	}
}