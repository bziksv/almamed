<?php

class shopSeofilterSitemapCachedSitemap implements shopSeofilterISitemap
{
	const PRIORITY = 0.6;
	const LIMIT = 30000;

	private $urls = array();
	private $storefront;
	private $currency;
	private $consider_category_filters;
	private $in_stock_only;
	private $route;
	private $seofilter_url;
	private $categories;
	private $alternative_storefront;

	/**
	 * shopSeofilterSitemapCachedSitemap constructor.
	 * @param array $route
	 * @param string $currency
	 * @param bool $consider_category_filters
	 */
	public function __construct($route, $currency, $consider_category_filters)
	{
		$this->route = $route;
		$this->currency = strtoupper($currency);
		$this->consider_category_filters = $consider_category_filters;

		$this->storefront = $route['domain'] . '/' . $route['url'];
		$this->in_stock_only = array_key_exists('drop_out_of_stock', $route) && $route['drop_out_of_stock'] == 2;

		$settings = shopSeofilterBasicSettingsModel::getSettings();

		$this->alternative_storefront = $this->in_stock_only
			? $settings->sitemap_cache_default_storefront_show_products
			: $settings->sitemap_cache_default_storefront_hide_products;

		$this->seofilter_url = new shopSeofilterFilterUrl($settings->url_type, $route['url_type'], null, $route['domain'], $route);

		$model = new shopCategoryModel();
		$sql = "SELECT c.*
                FROM shop_category c
                LEFT JOIN shop_category_routes cr ON c.id = cr.category_id
                WHERE c.status = 1 AND (cr.route IS NULL OR cr.route = '" . $this->storefront . "')
                ORDER BY c.left_key";
		$this->categories = $model->query($sql)->fetchAll('id');
	}

	/**
	 * @param int $page if set to shopSeofilterISitemap::ALL_URLS returns all urls
	 * @return array
	 */
	public function generate($page = 1)
	{
		$cache_model = new shopSeofilterSitemapCacheModel();
		$cache_by_category = $cache_model->getByStorefrontQuery($this->storefront, $this->in_stock_only, $this->consider_category_filters);
		$filter_model = new shopSeofilterFilterModel();

		$filter_urls_cache = array();

		$offset = 0;
		$min_offset = $max_offset = 0;

		if ($page !== self::ALL_URLS)
		{
			$min_offset = self::LIMIT * ($page - 1);
			$max_offset = self::LIMIT * $page;
		}

		foreach ($cache_by_category as $data)
		{
			$category_id = $data['category_id'];
			$filter_ids = explode(',', $data['filter_ids']);
			if (!isset($this->categories[$category_id]))
			{
				continue;
			}

			if ($page !== self::ALL_URLS)
			{
				if ($offset > $max_offset)
				{
					break;
				}

				$start = $offset;
				$offset += count($filter_ids);
				if ($offset < $min_offset)
				{
					continue;
				}

				$from = max($min_offset - $start, 0);
				$to = min($from + self::LIMIT, count($filter_ids));
			}
			else
			{
				$from = 0;
				$to = count($filter_ids);
			}

			$filters_to_load = array();
			$filter_urls = array();
			for ($index = $from; $index < $to; $index++)
			{
				$filter_id = $filter_ids[$index];

				if (isset($filter_urls_cache[$filter_id]))
				{
					$filter_urls[] = $filter_urls_cache[$filter_id];
				}
				else
				{
					$filters_to_load[] = $filter_id;
				}
			}

			foreach ($filter_model->getFilterUrls($filters_to_load) as $row)
			{
				$filter_urls_cache[$row['id']] = $row['url'];
				$this->addUrl($this->categories[$category_id], $row['url'], $data['lastmod']);
			}

			foreach ($filter_urls as $filter_url)
			{
				$this->addUrl($this->categories[$category_id], $filter_url, $data['lastmod']);
			}

			if ($page !== self::ALL_URLS && $offset == $max_offset)
			{
				break;
			}
		}

		return $this->urls;
	}

	/**
	 * @return int
	 */
	public function countPages()
	{
		$count = $this->countLinks();

		return ceil($count / self::LIMIT - 1e-6);
	}

	public function countLinks()
	{
		$cache_model = new shopSeofilterSitemapCacheModel();
		$cache_by_category = $cache_model->getByStorefrontQuery($this->storefront, $this->in_stock_only, $this->consider_category_filters);

		$count = 0;
		foreach ($cache_by_category as $data)
		{
			$ids = explode(',', $data['filter_ids']);
			$count += count($ids);
		}

		return $count;
	}

	/**
	 * @param array $category
	 * @param string $filter_url
	 * @param string $lastmod
	 */
	private function addUrl($category, $filter_url, $lastmod)
	{
		$url = $this->seofilter_url->getFrontendPageUrl($category, $filter_url, true);

		$this->urls[] = array(
			'loc' => $url,
			'lastmod' => $lastmod,
			'changefreq' => shopSitemapConfig::CHANGE_WEEKLY,
			'priority' => self::PRIORITY,
		);
	}
}