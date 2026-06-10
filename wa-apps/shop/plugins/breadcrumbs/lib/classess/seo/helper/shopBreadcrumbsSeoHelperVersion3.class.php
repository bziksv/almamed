<?php

class shopBreadcrumbsSeoHelperVersion3 extends shopBreadcrumbsAbstractSeoHelper
{
	public function extendCategory($storefront, $category, $page)
	{
		$category_extender = shopSeoContext::getInstance()->getCategoryExtender();
		$category_extended = $category_extender->extend(
			$storefront,
			$category,
			$page
		);

		return $category_extended;
	}

	public function getSeoNames($storefront, $category_ids)
	{
		$category_seo_names = array();
		foreach ($category_ids as $category_id)
		{
			$category_seo_names[$category_id] = shopSeoContext::getInstance()
				->getCategoryDataCollector()
				->collectSeoName($storefront, $category_id, $_);
		}

		return $category_seo_names;
	}

	public function isPluginEnabled()
	{
		return shopSeoPlugin::isEnabled();
	}

	public function getCategoryH1($storefront, $category)
	{
		$h1 = $category['name'];

		$view = wa()->getView();

		$view_category = $view->getVars('category');
		if (is_array($view_category) && $view_category['id'] == $category['id'])
		{
			$tmp = $view_category;
			unset($tmp['id']);
			$view->assign('category', $tmp);
		}
		unset($tmp);


		$collector = shopSeoContext::getInstance()->getCategoryDataCollector();
		$renderer = shopSeoContext::getInstance()->getCategoryDataRenderer();

		$templates = $collector->collect($storefront, $category['id'], false, $info);
		$seo_h1 = $renderer->render($storefront, $category['id'], 1, $templates['h1']);
		if (trim($seo_h1) != '')
		{
			$h1 = $seo_h1;
		}


		if (is_array($view_category) && $view_category['id'] == $category['id'])
		{
			$view->assign('category', $view_category);
		}

		return $h1;
	}

	public function getCategorySeoName($storefront, $category)
	{
		return shopSeoContext::getInstance()
			->getCategoryDataCollector()
			->collectSeoName($storefront, $category['id'], $_);
	}

	public function getProductH1($storefront, $product)
	{
		$h1 = $product['name'];

		$collector = shopSeoContext::getInstance()->getProductDataCollector();
		$renderer = shopSeoContext::getInstance()->getProductDataRenderer();

		$templates = $collector->collect($storefront, $product['id'], $info);
		$seo_h1 = $renderer->render($storefront, $product['id'], $templates['h1']);
		if (trim($seo_h1) != '')
		{
			$h1 = $seo_h1;
		}

		return $h1;
	}

	public function getProductPageH1($storefront, $product, $page)
	{
		$h1 = $page['title'];

		$collector = shopSeoContext::getInstance()->getProductPageDataCollector();
		$renderer = shopSeoContext::getInstance()->getProductDataRenderer();

		$templates = $collector->collect($storefront, $product['id'], $page['id'], $info);
		$seo_h1 = $renderer->render($storefront, $product['id'], $templates['h1']);
		if (trim($seo_h1) != '')
		{
			$h1 = $seo_h1;
		}

		return $h1;
	}

	public function getShopPageH1($storefront, $page)
	{
		return $page['name'];
	}

	public function getBrandH1($storefront, $brand)
	{
		$h1 = $brand['name'];

		$collector = shopSeoContext::getInstance()->getBrandDataCollector();
		$renderer = shopSeoContext::getInstance()->getBrandDataRenderer();

		$templates = $collector->collect($storefront, $brand['id'], $info);
		$seo_h1 = $renderer->render($storefront, 1, $templates['h1']);
		if (trim($seo_h1) != '')
		{
			$h1 = $seo_h1;
		}

		return $h1;
	}
}