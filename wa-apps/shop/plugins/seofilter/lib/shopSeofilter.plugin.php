<?php
/*
 * todo поправить блокировку
 * не блокируется то что надо
 * в частности, не блокируется на странице сеофильтра
 */
class shopSeofilterPlugin extends shopPlugin
{
	public function handleRightsConfig(waRightConfig $config)
	{
		$handler = new shopSeofilterRightsConfigHandler($config);

		$handler->run();
	}

	public function routing($route = null)
	{
		$handler = new shopSeofilterRoutingHandler($route);

		return $handler->run();
	}

	public function handleFrontendCategory()
	{
		$handler = new shopSeofilterFrontendCategoryHandler();

		return $handler->run();
	}

	public function handleFrontendHead()
	{
		$handler = new shopSeofilterFrontendHeadHandler();

		return $handler->run();
	}

	public function handleSeoAssignCase($case_type)
	{
		$handler = new shopSeofilterSeoAssignCaseHandler($case_type);

		return $handler->run();
	}

	public function handleBackendMenu()
	{
		$handler = new shopSeofilterBackendMenuHandler();

		return $handler->run();
	}

	public function handleSitemap($route)
	{
		$handler = new shopSeofilterSitemapHandler($route);

		return $handler->run();
	}

	public function handleAppSitemapIndexSitemap($route)
	{
		$handler = new shopSeofilterAppSitemapIndexSitemapHandler($route);

		return $handler->run();
	}

	public function handleAppSitemapStructure()
	{
		$handler = new shopSeofilterAppSitemapStructureHandler();

		return $handler->run();
	}


	//для обновления кеша sitemap

	public function handleProductSave($params)
	{
		$handler = new shopSeofilterProductSaveHandler($params);

		$handler->run();
	}

	public function handleProductSkuDelete($sku)
	{
		$handler = new shopSeofilterProductSkuDeleteHandler($sku);

		$handler->run();
	}

	public function handleProductDelete($params)
	{
		$handler = new shopSeofilterProductDeleteHandler($params);

		$handler->run();
	}

	public function handleProductMassUpdate($params)
	{
		$handler = new shopSeofilterProductMassUpdateHandler($params);

		$handler->run();
	}

	public function handleCategoryDelete($category)
	{
		$handler = new shopSeofilterCategoryDeleteHandler($category);

		$handler->run();
	}

	public function handleCategorySave($category)
	{
		$handler = new shopSeofilterCategorySaveHandler($category);

		$handler->run();
	}
}
