<?php

class shopBreadcrumbsSeoHelperVersion2 extends shopBreadcrumbsAbstractSeoHelper
{
	public function isPluginEnabled()
	{
		return shopBreadcrumbsSeoHelper::isPluginInstalled() && shopSeoSettings::isEnablePlugin();
	}

	public function getCategoryH1($storefront, $category)
	{
		if (array_key_exists('original_name', $category))
		{
			return $category['original_name'];
		}

		$view = wa()->getView();

		$h1 = $category['name'];
		$category_id = $category['id'];

		$collector = new shopSeoCategoryCollector($storefront, $category_id, 1);
		$category_data = $collector->getData();

		$view_category = $view->getVars('category');
		if (is_array($view_category) && $view_category['id'] == $category_id)
		{
			$tmp = $view_category;
			unset($tmp['id']);
			$view->assign('category', $tmp);
		}
		unset($tmp);

		$h1_template = ifset($category_data['h1']);
		if ($h1_template)
		{
			$case = new shopSeoCategoryCase($storefront, $category_id, 1);

			$seo_h1 = $case->fetch($h1_template, false);
			if (trim($seo_h1) != '')
			{
				$h1 = $seo_h1;
			}
		}

		if (is_array($view_category) && $view_category['id'] == $category_id)
		{
			$view->assign('category', $view_category);
		}

		return $h1;
	}

	public function getCategorySeoName($storefront, $category)
	{
		return shopSeoViewHelper::getCategorySeoName($category['id'], $storefront);
	}

	public function getProductH1($storefront, $product)
	{
		$collector = new shopSeoProductCollector($storefront, $product['id']);

		$templates = $collector->getData();

		$h1 = $product['name'];

		$h1_template = ifset($templates['h1']);
		if ($h1_template)
		{
			$case = new shopSeoProductCase($storefront, $product['id']);

			$seo_h1 = $case->fetch($h1_template);
			if (trim($seo_h1) != '')
			{
				$h1 = $seo_h1;
			}
		}

		return $h1;
	}

	public function getProductPageH1($storefront, $product, $page)
	{
		$h1 = $page['title'];

		$collector = new shopSeoProductPageCollector($storefront, $product['id'], $page['id']);

		$templates = $collector->getData();

		$h1_template = ifset($templates['h1']);
		if ($h1_template)
		{
			$case = new shopSeoProductCase($storefront, $product['id']);

			$seo_h1 = $case->fetch($h1_template);
			if (trim($seo_h1) != '')
			{
				$h1 = $seo_h1;
			}
		}

		return $h1;
	}

	public function getShopPageH1($storefront, $page)
	{
		$h1 = $page['name'];

		$collector = new shopSeoPageCollector($storefront, $page['id']);

		$templates = $collector->getData();
		$h1_template = ifset($templates['h1']);

		if ($h1_template)
		{
			$case = new shopSeoPageCase($storefront, $page['id']);

			$seo_h1 = $case->fetch($h1_template);
			if (trim($seo_h1) != '')
			{
				$h1 = $seo_h1;
			}
		}

		return $h1;
	}

	public function getBrandH1($storefront, $brand)
	{
		$h1 = $brand['name'];

		$collector = new shopSeoBrandCollector($storefront, $brand['id']);

		$templates = $collector->getData();

		$h1_template = ifset($templates['h1']);
		if ($h1_template)
		{
			$case = new shopSeoBrandCase($storefront, $brand['id']);

			$seo_h1 = $case->fetch($h1_template);
			if (trim($seo_h1) != '')
			{
				$h1 = $seo_h1;
			}
		}

		return $h1;
	}
}