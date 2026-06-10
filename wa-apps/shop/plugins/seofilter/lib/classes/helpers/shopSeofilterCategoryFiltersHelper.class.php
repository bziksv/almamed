<?php

class shopSeofilterCategoryFiltersHelper
{
	public static function getCategoryFilters($domain, $route = null, $absolute = false)
	{
		if ($route === null)
		{
			$routes = wa()->getRouting()->getByApp('shop');
			foreach ($routes as $route_domain => $domain_routes)
			{
				if (count($domain_routes))
				{
					$domain = $route_domain;
					$route = reset($domain_routes);

					break;
				}
			}
		}

		$category_model = new shopCategoryModel();

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$filter_url = new shopSeofilterFilterUrl($settings->url_type, ifset($route['url_type'], 0));

		$categories = $category_model->getAll('id');
		$storefront = $domain . '/' . $route['url'];

		$filter_ar = new shopSeofilterFilter();
		$cache_model = new shopSeofilterSitemapCacheModel();
		$cache_by_category = $cache_model->getByStorefrontQuery($storefront, ifset($route['drop_out_of_stock']) == 2, $settings->consider_category_filters);

		$filters = array();
		$category_filters = array();
		foreach ($cache_by_category as $data)
		{
			$category_id = $data['category_id'];
			$category = ifset($categories[$category_id]);
			if (!$category)
			{
				continue;
			}

			$category_filters[$category_id] = array();

			$filter_ids = explode(',', $data['filter_ids']);
			foreach ($filter_ids as $filter_id)
			{
				$filter = ifset($filters[$filter_id]);
				if ($filter === false)
				{
					continue;
				}

				if ($filter === null)
				{
					$filter = $filter_ar->getById($filter_id);
					if (!$filter)
					{
						$filters[$filter_id] = false;
						continue;
					}

					$filters[$filter_id] = $filter;
				}

				$filter_attributes = new shopSeofilterFilterAttributes($filter);
				$filter_attributes->setFullUrl($filter_url->getFrontendPageUrl($category, $filter, $absolute, $domain, $route['url']));
				$category_filters[$category_id][] = $filter_attributes;
			}
		}

		return $category_filters;
	}
}