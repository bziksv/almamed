<?php

class shopBreadcrumbsSeoExtenderCache
{
	const CACHE_CATEGORY = 'category';
	const CACHE_PRODUCT = 'product';
	const CACHE_PRODUCT_PAGE = 'product_page';
	const CACHE_SHOP_PAGE = 'shop_page';
	const CACHE_BRAND = 'shop_page';

	const DEFAULT_CACHE_CHUNK_SIZE = 100;

	private $storefront;
	private $cache_group;
	private $entity_id;
	private $ttl;

	public function __construct($storefront, $cache_group, $entity_id, $ttl_seconds = 300)
	{
		$this->storefront = $storefront;
		$this->cache_group = $cache_group;
		$this->entity_id = $entity_id;
		$this->ttl = $ttl_seconds;
	}

	public static function getCachePath()
	{
		return wa()->getCachePath('cache/shop_breadcrumbs_plugin/', 'shop');
	}

	public function get()
	{
		$cache = $this->getCache();

		if (!$cache->isCached())
		{
			return null;
		}

		$data = $cache->get();
		if (!array_key_exists($this->entity_id, $data))
		{
			return null;
		}

		$extension = $data[$this->entity_id];
		$field = $this->extensionStoreTimeField();

		if (is_array($extension))
		{
			if (isset($extension[$field]) && (time() - $extension[$field] > $this->ttl))
			{
				return null;
			}

			unset($extension[$field]);
		}

		return $extension;
	}

	public function set($entity_value)
	{
		$cache = $this->getCache();

		$data = $cache->isCached()
			? $cache->get()
			: array();

		if (is_array($entity_value))
		{
			$entity_value[$this->extensionStoreTimeField()] = time();
		}

		$data[$this->entity_id] = $entity_value;

		$cache->set($data);
	}

	/**
	 * @return waSerializeCache
	 */
	private function getCache()
	{
		$chunk_size = $this->getGroupChunkSize($this->cache_group);
		$group_id = (int)($this->entity_id / $chunk_size);

		$key = 'extender/' . $this->cache_group . '/' . $group_id;

		$cache_dir = 'shop_breadcrumbs_plugin/' . md5($this->storefront) . '/';

		return new waSerializeCache($cache_dir . md5($key), $this->ttl, 'shop');
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