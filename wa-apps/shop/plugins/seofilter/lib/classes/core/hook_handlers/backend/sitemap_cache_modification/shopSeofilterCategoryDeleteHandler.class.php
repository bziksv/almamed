<?php

class shopSeofilterCategoryDeleteHandler extends shopSeofilterCatalogModificationHookHandler
{
	protected function handle()
	{
		$this->sitemap_cache->removeForCategory($this->params);
	}
}