<?php

class shopBreadcrumbsSeoExtender
{
	private static $_seo_helper = null;

	private $seo_helper;
	private $is_seo_enabled;
	private $storefront;

	public function __construct()
	{
		if (self::$_seo_helper === null)
		{
			self::$_seo_helper = new shopBreadcrumbsSeoHelper();
		}

		$this->seo_helper = self::$_seo_helper;
		$this->is_seo_enabled = $this->seo_helper->isPluginEnabled();
		$this->storefront = shopBreadcrumbsPlugin::getStorefront();
	}

	public function extendCategory($category, $with_brothers, $category_name_mode)
	{
		if (!$category)
		{
			return $category;
		}

		$cache = $this->getCache(shopBreadcrumbsSeoExtenderCache::CACHE_CATEGORY, $category['id']);

		$category_extension = $cache->get();
		if ($category_extension === null)
		{
			$category_extension = array(
				'name' => $category['name'],
			);

			if ($this->is_seo_enabled)
			{
				if ($category_name_mode == shopBreadcrumbsSettings::CATEGORY_NAME_MODE_NAME && array_key_exists('original_name', $category))
				{
					$category_extension = array(
						'name' => $category['original_name'],
					);
				}
				elseif ($category_name_mode == shopBreadcrumbsSettings::CATEGORY_NAME_MODE_SEO_H1)
				{
					$h1 = $this->seo_helper->getCategoryH1($this->storefront, $category);

					if (trim($h1) !== '')
					{
						$category_extension['name'] = $h1;
					}
					elseif (array_key_exists('original_name', $category))
					{
						$category_extension['name'] = $category['original_name'];
					}
				}
				elseif ($category_name_mode == shopBreadcrumbsSettings::CATEGORY_NAME_MODE_SEO_NAME)
				{
					$seo_name = $this->seo_helper->getCategorySeoName($this->storefront, $category);

					if (trim($seo_name) !== '')
					{
						$category_extension['name'] = $seo_name;
					}
					elseif (array_key_exists('original_name', $category))
					{
						$category_extension['name'] = $category['original_name'];
					}
				}
			}

			$cache->set($category_extension);
		}

		if ($with_brothers)
		{
			$category['brothers'] = $this->getCategoryBrothers($category, $category_name_mode);
		}

		foreach ($category_extension as $key => $value)
		{
			$category[$key] = $value;
		}

		return $category;
	}

	public function extendProduct($product)
	{
		if (!$this->is_seo_enabled || !$product)
		{
			return $product;
		}

		$cache = $this->getCache(shopBreadcrumbsSeoExtenderCache::CACHE_PRODUCT, $product['id']);
		$product_extension = $cache->get();

		if ($product_extension === null)
		{
			$product_extension = array(
				'name' => $this->seo_helper->getProductH1($this->storefront, $product),
			);

			$cache->set($product_extension);
		}

		foreach ($product_extension as $key => $value)
		{
			$product[$key] = $value;
		}

		return $product;
	}

	public function extendProductPage($product, $page)
	{
		if (!$this->is_seo_enabled || !$page || !$product)
		{
			return $page;
		}

		$cache = $this->getCache(shopBreadcrumbsSeoExtenderCache::CACHE_PRODUCT_PAGE, $page['id']);
		$page_extension = $cache->get();

		if ($page_extension === null)
		{
			$page_extension = array(
				'title' => $this->seo_helper->getProductPageH1($this->storefront, $product, $page),
			);

			$cache->set($page_extension);
		}

		foreach ($page_extension as $key => $value)
		{
			$page[$key] = $value;
		}

		return $page;
	}

	public function extendShopPage($page)
	{
		if (!$this->is_seo_enabled || !$page)
		{
			return $page;
		}

		$cache = $this->getCache(shopBreadcrumbsSeoExtenderCache::CACHE_PRODUCT_PAGE, $page['id']);
		$page_extension = $cache->get();

		if ($page_extension === null)
		{
			$page_extension = array(
				'name' => $this->seo_helper->getShopPageH1($this->storefront, $page),
			);

			$cache->set($page_extension);
		}

		foreach ($page_extension as $key => $value)
		{
			$page[$key] = $value;
		}

		return $page;
	}

	public function extendBrand($brand)
	{
		if (!$this->is_seo_enabled || !$brand)
		{
			return $brand;
		}

		$cache = $this->getCache(shopBreadcrumbsSeoExtenderCache::CACHE_BRAND, $brand['id']);
		$brand_extension = $cache->get();

		if ($brand_extension === null)
		{
			$brand_extension = array(
				'name' => $this->seo_helper->getBrandH1($this->storefront, $brand),
			);

			$cache->set($brand_extension);
		}

		foreach ($brand_extension as $key => $value)
		{
			$brand[$key] = $value;
		}

		return $brand;
	}

	/**
	 * @param $category
	 * @param $category_name_mode
	 * @return array
	 */
	public function getCategoryBrothers($category, $category_name_mode)
	{
		$model = new waModel();

		$category_items_sql = '
SELECT c.id, c.name AS `name`, c.url, c.full_url, c.type
FROM shop_category AS c
	LEFT JOIN shop_category_routes AS route
		ON route.category_id = c.id
WHERE
	(route.route IS NULL OR route.route = :storefront)
	AND c.parent_id = :parent_id
	AND c.status = 1
ORDER BY c.left_key
';

		$brothers = array();

		if (array_key_exists('parent_id', $category))
		{
			$params = array(
				'parent_id' => $category['parent_id'] ? $category['parent_id'] : 0,
				'storefront' => $this->storefront,
			);

			$brothers_query = $model->query($category_items_sql, $params);

			foreach ($brothers_query as $brother)
			{
				$brother['brothers'] = array();

				$brothers[$brother['id']] = $brother['type'] == shopCategoryModel::TYPE_STATIC
					? $this->extendCategory($brother, false, $category_name_mode)
					: $brother;
			}
		}

		return $brothers;
	}

	private function getCache($cache_group, $entity_id)
	{
		return new shopBreadcrumbsSeoExtenderCache($this->storefront, $cache_group, $entity_id, 300);
	}
}