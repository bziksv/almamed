<?php

class shopSeofilterProductDeleteHandler extends shopSeofilterCatalogModificationHookHandler
{
	protected function handle()
	{
		$product_ids = ifset($this->params['ids']);

		if (is_array($product_ids) && count($product_ids))
		{
			$this->sitemap_cache->invalidateByProductId($product_ids);
		}
	}
}