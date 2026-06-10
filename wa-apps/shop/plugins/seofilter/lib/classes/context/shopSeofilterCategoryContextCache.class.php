<?php

class shopSeofilterCategoryContextCache
{
	const DEFAULT_CACHE_CHUNK_SIZE = 100;

	private $storefront;
	private $category_id;
	private $currency;
	private $filter_id;
	private $ttl;

	public function __construct($storefront, $category_id, $currency, $filter_id, $ttl_seconds = 300)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();

		$this->storefront = $settings->cache_for_single_storefront
			? '*'
			: $storefront;
		$this->category_id = $category_id;
		$this->currency = $currency;
		$this->filter_id = $filter_id;
		$this->ttl = $ttl_seconds;
	}

	//public static function getCachePath()
	//{
	//	return wa()->getCachePath('cache/shop_breadcrumbs_plugin/', 'shop');
	//}

	public function get()
	{
		$cache = $this->getCache();

		if (!$cache->isCached())
		{
			return null;
		}

		$data = $cache->get();

		if (
			!is_array($data)
			|| !array_key_exists($this->category_id, $data)
			|| !is_array($data[$this->category_id])
			|| !array_key_exists($this->filter_id, $data[$this->category_id])
		)
		{
			return null;
		}

		$entity_value = $data[$this->category_id][$this->filter_id];
		$field = $this->extensionStoreTimeField();

		if (is_array($entity_value))
		{
			if (isset($entity_value[$field]) && (time() - $entity_value[$field] > $this->ttl))
			{
				return null;
			}

			unset($entity_value[$field]);
		}

		return $entity_value;
	}

	public function set($entity_value)
	{
		$cache = $this->getCache();

		$data = $cache->isCached()
			? $cache->get()
			: array();

		if (!array_key_exists($this->category_id, $data))
		{
			$data[$this->category_id] = array();
		}

		if (is_array($entity_value))
		{
			$entity_value[$this->extensionStoreTimeField()] = time();
		}

		$data[$this->category_id][$this->filter_id] = $entity_value;

		$cache->set($data);
	}

	/**
	 * @return waSerializeCache
	 */
	private function getCache()
	{
		$chunk_size = $this->getGroupChunkSize($this->category_id);

		$category_range_key = (int)($this->category_id / $chunk_size);
		$filter_range_key = (int)($this->filter_id / $chunk_size);

		$cache_path = 'seofilter/category_context/' . md5("{$this->storefront}:{$this->currency}:{$category_range_key}:{$filter_range_key}");

		return new waSerializeCache($cache_path, $this->ttl, 'shop');
	}

	private function getGroupChunkSize($cache_group)
	{
		return self::DEFAULT_CACHE_CHUNK_SIZE;
	}

	private function extensionStoreTimeField()
	{
		return '_store_timestamp';
	}
}
