<?php

class shopSeofilterProductSkuDeleteHandler extends shopSeofilterCatalogModificationHookHandler
{
	protected function handle()
	{
		$this->sitemap_cache->invalidateByProductId(ifset($this->params['product_id']));
	}
}