<?php

class shopSeofilterSeoHelper extends shopSeofilterAbstractSeoHelper
{
	private static $info = null;

	/** @var shopSeofilterAbstractSeoHelper|null */
	private $version_helper = null;

	public function __construct()
	{
		$this->version_helper = $this->getVersionHelper();
	}

	public function getFrontendCategorySeoData($storefront, $category, $page, $view)
	{
		if ($this->version_helper)
		{
			return $this->version_helper->getFrontendCategorySeoData($storefront, $category, $page, $view);
		}
		else
		{
			$seo_data = new shopSeofilterSeoHelperFrontendCategorySeoData();
			
			$this->fillFrontendCategorySeoDataEmptyFields($seo_data, $category);

			return $seo_data;
		}
	}

	public function getCategoryData($storefront, $category, $page, $fields_to_fetch)
	{
		return $this->version_helper
			? $this->version_helper->getCategoryData($storefront, $category, $page, $fields_to_fetch)
			: new shopSeofilterSeoHelperCategoryData();
	}

	public function extendCategory($storefront, $category, $page)
	{
		return $this->version_helper
			? $this->version_helper->extendCategory($storefront, $category, $page)
			: $category;
	}

	public function getSeoNames($storefront, $category_ids)
	{
		return $this->version_helper
			? $this->version_helper->getSeoNames($storefront, $category_ids)
			: array();
	}

	public function isPluginEnabled()
	{
		return $this->version_helper && $this->version_helper->isPluginEnabled();
	}

	public function getCategoryCustomFieldNames()
	{
		return $this->version_helper
			? $this->version_helper->getCategoryCustomFieldNames()
			: array();
	}

	private function getVersionHelper()
	{
		if (!self::isPluginInstalled())
		{
			return null;
		}

		$info = self::getPluginInfo();
		$version = ifset($info['version']);

		if (version_compare($version, '2.22', '>=') && version_compare($version, '3', '<'))
		{
			return new shopSeofilterSeoHelperVersion2();
		}

		if (version_compare($version, '3', '>=') && version_compare($version, '4', '<'))
		{
			return new shopSeofilterSeoHelperVersion3();
		}

		return null;
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