<?php

class shopSeofilterProductMassUpdateHandler extends shopSeofilterCatalogModificationHookHandler
{
	protected function handle()
	{
		$product_ids = array();
		foreach ($this->params['skus_changed'] as $sku)
		{
			if (isset($sku['product_id']))
			{
				$product_ids[$sku['product_id']] = 1;
			}
		}

		if (count($product_ids))
		{
			$this->sitemap_cache->invalidateByProductId(array_keys($product_ids));
		}
	}
}