<?php

class shopSeofilterProductSaveHandler extends shopSeofilterCatalogModificationHookHandler
{
	protected function handle()
	{
		$this->sitemap_cache->invalidateByProductId(ifset($this->params['data']['id']));
	}
}