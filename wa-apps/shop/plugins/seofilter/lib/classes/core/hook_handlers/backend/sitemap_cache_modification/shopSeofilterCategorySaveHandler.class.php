<?php

class shopSeofilterCategorySaveHandler extends shopSeofilterCatalogModificationHookHandler
{
	protected function handle()
	{
		$category_id = ifset($this->params['id']);

		if ($category_id)
		{
			$this->sitemap_cache->invalidateForCategories(array($category_id));
		}
	}
}