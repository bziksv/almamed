<?php

class shopSeofilterFiltersCachedFrontendStorage
{
	private $filters_storage;

	private $cache_objects = array();
	private $cache_ttl_seconds;

	public function __construct(shopSeofilterPluginSettings $settings)
	{
		$this->filters_storage = new shopSeofilterFiltersFrontendStorage();

		$this->cache_ttl_seconds = $settings->cache_ttl_minutes * 60 * (1 + rand(-25, 25) * 0.01);
	}

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @param array $filter_params
	 * @param string $currency
	 * @return shopSeofilterFilterCached|null
	 */
	public function getByFilterParams($storefront, $category_id, $filter_params, $currency)
	{
		$cache_key = $this->getCacheKey($storefront, $category_id, $filter_params, $currency);

		if ($this->isCached($cache_key))
		{
			return $this->getFromCache($cache_key);
		}


		$filter = $this->filters_storage->getByFilterParams($storefront, $category_id, $filter_params, $currency);

		$filter_cached = $filter ? new shopSeofilterFilterCached($filter) : null;
		$this->storeToCache($cache_key, $filter_cached);

		return $filter_cached;
	}

	private function getCacheKey($storefront, $category_id, $filter_params, $currency)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();

		$storefront_key = $settings->cache_for_single_storefront
			? md5('*')
			: md5($storefront);
		$path_key = "seofilter/cached_filters/{$storefront_key}/{$category_id}";

		$filter_params_normalized = shopSeofilterFilterFeatureValuesHelper::normalizeParams($filter_params);

		$data_key = count($filter_params_normalized) == 0
			? 'empty'
			: sha1(http_build_query($filter_params_normalized, null, '&'));

		return array($path_key, $data_key . '_' . $currency);
	}

	/**
	 * @param array $cache_key
	 * @param shopSeofilterFilterCached|null $filter_cached
	 */
	private function storeToCache($cache_key, $filter_cached)
	{
		$cache = $this->getCache($cache_key);

		$cached_filters = $cache->isCached()
			? $cache->get()
			: array();

		$cached_filters[$cache_key[1]] = $filter_cached;

		$cache->set($cached_filters);
	}

	/**
	 * @param array $cache_key
	 * @return bool
	 */
	private function isCached($cache_key)
	{
		$cache = $this->getCache($cache_key);

		$cached_filters = $cache->isCached()
			? $cache->get()
			: array();

		return array_key_exists($cache_key[1], $cached_filters);
	}

	/**
	 * @param array $cache_key
	 * @return shopSeofilterFilterCached|null
	 */
	private function getFromCache($cache_key)
	{
		$cache = $this->getCache($cache_key);

		$cached_filters = $cache->isCached()
			? $cache->get()
			: array();

		return $cached_filters[$cache_key[1]];
	}

	/**
	 * @param array $cache_key
	 * @return waSerializeCache
	 */
	private function getCache($cache_key)
	{
		if (!array_key_exists($cache_key[0], $this->cache_objects))
		{
			$this->cache_objects[$cache_key[0]] = new waSerializeCache($cache_key[0], $this->cache_ttl_seconds, 'shop');
		}

		return $this->cache_objects[$cache_key[0]];
	}
}
