<?php

class shopBreadcrumbsSeoHelper extends shopBreadcrumbsAbstractSeoHelper
{
	private static $info = null;

	/** @var shopBreadcrumbsAbstractSeoHelper */
	private $version_helper = null;

	public function __construct()
	{
		$this->version_helper = $this->getVersionHelper();
	}

	public function isPluginEnabled()
	{
		return $this->version_helper->isPluginEnabled();
	}

	public function getCategoryH1($storefront, $category)
	{
		return $this->version_helper->getCategoryH1($storefront, $category);
	}

	public function getCategorySeoName($storefront, $category)
	{
		return $this->version_helper->getCategorySeoName($storefront, $category);
	}

	public function getProductH1($storefront, $product)
	{
		return $this->version_helper->getProductH1($storefront, $product);
	}

	public function getProductPageH1($storefront, $product, $page)
	{
		return $this->version_helper->getProductPageH1($storefront, $product, $page);
	}

	public function getShopPageH1($storefront, $page)
	{
		return $this->version_helper->getShopPageH1($storefront, $page);
	}

	public function getBrandH1($storefront, $brand)
	{
		return $this->version_helper->getBrandH1($storefront, $brand);
	}

	private function getVersionHelper()
	{
		if (!self::isPluginInstalled())
		{
			return new shopBreadcrumbsSeoHelperNotEnabled();
		}

		$info = self::getPluginInfo();
		$version = ifset($info['version']);

		if (version_compare($version, '2.22', '>=') && version_compare($version, '3', '<'))
		{
			return new shopBreadcrumbsSeoHelperVersion2();
		}

		if (version_compare($version, '3', '>=') && version_compare($version, '4', '<'))
		{
			return new shopBreadcrumbsSeoHelperVersion3();
		}

		return new shopBreadcrumbsSeoHelperNotEnabled();
	}

	public static function isPluginInstalled()
	{
		return self::getPluginInfo() !== array();
	}

	private static function getPluginInfo()
	{
		if (self::$info === null)
		{
			self::$info = wa('shop')->getConfig()->getPluginInfo('seo');
		}

		return self::$info;
	}
}