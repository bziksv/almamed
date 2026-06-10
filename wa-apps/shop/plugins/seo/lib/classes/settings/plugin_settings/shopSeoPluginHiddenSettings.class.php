<?php

class shopSeoPluginHiddenSettings
{
	private $cache_for_single_storefront = false;

	public function isCacheForSingleStorefront()
	{
		return $this->cache_for_single_storefront;
	}

	public function setCacheForSingleStorefront($cache_for_single_storefront)
	{
		$this->cache_for_single_storefront = $cache_for_single_storefront;
	}
}
