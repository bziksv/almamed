<?php

abstract class shopSeofilterCatalogModificationHookHandler extends shopSeofilterHookHandler
{
	/** @var shopSeofilterSitemapCache */
	private static $_sitemap_cache = null;

	/** @var shopSeofilterSitemapCache */
	protected $sitemap_cache;

	public function __construct($params = null)
	{
		parent::__construct($params);

		if (self::$_sitemap_cache === null)
		{
			self::$_sitemap_cache = new shopSeofilterSitemapCache();
		}

		$this->sitemap_cache = self::$_sitemap_cache;
	}

	public function run()
	{
		if ($this->beforeHandle())
		{
			$this->handle();
		}
	}

	protected function beforeHandle()
	{
		return !$this->settings->disable_on_save_handlers;
	}
}