<?php

class shopSearchproV2ResultCache
{
	private $ttl;
	private $group;

	public function __construct($ttl, $group = 'shop/searchpro/v2')
	{
		$this->ttl = max(0, (int) $ttl);
		$this->group = $group;
	}

	public function get($context, $query, $category_id, array $extra = array())
	{
		if ($this->ttl === 0) {
			return null;
		}

		$key = $this->buildKey($context, $query, $category_id, $extra);
		$cache = new waSerializeCache($key, $this->ttl, $this->group);

		if (!$cache->isCached()) {
			return null;
		}

		$value = $cache->get();
		return is_array($value) ? $value : null;
	}

	public function set($context, $query, $category_id, array $payload, array $extra = array())
	{
		if ($this->ttl === 0) {
			return;
		}

		$key = $this->buildKey($context, $query, $category_id, $extra);
		$cache = new waSerializeCache($key, $this->ttl, $this->group);
		$cache->set($payload);
	}

	private function buildKey($context, $query, $category_id, array $extra)
	{
		$storefront = $this->getStorefrontKey();
		$hash = md5(json_encode(array(
			'context' => $context,
			'query' => $query,
			'category_id' => (int) $category_id,
			'storefront' => $storefront,
			'extra' => $extra,
			'version' => 8,
		)));

		return $context . '_' . $hash;
	}

	private function getStorefrontKey()
	{
		try {
			$env = shopSearchproPlugin::getEnv();
			return $env->getCurrentStorefront();
		} catch (Exception $e) {
			return wa()->getRouting()->getDomain(null, true);
		}
	}
}
