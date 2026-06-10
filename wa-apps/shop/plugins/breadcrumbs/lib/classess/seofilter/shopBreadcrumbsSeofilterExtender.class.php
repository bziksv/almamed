<?php

class shopBreadcrumbsSeofilterExtender
{
	const CACHE_CATEGORY_FILTER_H1 = 'category_filter_h1';

	private static $_is_seofilter_enabled = null;

	private $is_seofilter_enabled;
	private $storefront;

	public function __construct()
	{
		if (self::$_is_seofilter_enabled === null)
		{
			$helper = new shopBreadcrumbsHelper();
			self::$_is_seofilter_enabled = $helper->isSeofilterPluginEnabled();
		}

		$this->is_seofilter_enabled = self::$_is_seofilter_enabled;
		$this->storefront = shopBreadcrumbsPlugin::getStorefront();
	}

	/**
	 * @param $category_id
	 * @param shopSeofilterFilter $filter
	 * @return string|null
	 */
	public function getFilterH1($category_id, $filter)
	{
		if (!$filter || !$category_id)
		{
			return null;
		}

		$filter_id = $filter->id;
		$cache = $this->getCache(self::CACHE_CATEGORY_FILTER_H1, $category_id, $filter_id);

		$cache_chunk = $cache->get();

		if (!$cache->isCached() || !is_array($cache_chunk))
		{
			$cache_chunk = array();
		}

		if (!array_key_exists($category_id, $cache_chunk) || !is_array($cache_chunk[$category_id]))
		{
			$cache_chunk[$category_id] = array();
		}

		if (
			!array_key_exists($filter_id, $cache_chunk[$category_id])
			|| !is_string($cache_chunk[$category_id][$filter_id])
			|| strlen(trim($cache_chunk[$category_id][$filter_id]))
		)
		{
			$frontend_filter = new shopSeofilterFrontendFilter($this->storefront, $category_id, $filter);
			$context = new shopSeofilterCategoryContext(
				$frontend_filter,
				'',
				$this->storefront,
				$category_id,
				1
			);
			$context->prepareContext();
			$cache_chunk[$category_id][$filter_id] = $context->fetchFromBufferAll($frontend_filter->h1);

			$cache->set($cache_chunk);
		}

		return $cache_chunk[$category_id][$filter_id];
	}

	/**
	 * @param string $type
	 * @param int $category_id
	 * @param int $filter_id
	 * @return waSerializeCache
	 */
	private function getCache($type, $category_id, $filter_id)
	{
		$category_group_id = (int)($category_id / 10);
		$filter_group_id = (int)($filter_id / 300);
		$key = 'extender/' . $type . '/' . $category_group_id . '_' . $filter_group_id;

		$cache_dir = 'shop_breadcrumbs_plugin/' . md5($this->storefront) . '/';

		return new waSerializeCache($cache_dir . md5($key), 300, 'shop');
	}
}