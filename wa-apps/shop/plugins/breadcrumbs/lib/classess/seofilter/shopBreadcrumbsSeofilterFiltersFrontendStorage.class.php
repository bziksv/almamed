<?php

class shopBreadcrumbsSeofilterFiltersFrontendStorage
{
	/** @var shopSeofilterIFiltersStorage */
	private $storage;

	private $seofilter_version;

	public function __construct()
	{
		$this->storage = null;

		$helper = new shopBreadcrumbsHelper();
		if (!$helper->isSeofilterPluginEnabled())
		{
			return;
		}

		$info = wa('shop')->getConfig()->getPluginInfo('seofilter');
		$this->seofilter_version = is_array($info) && array_key_exists('version', $info)
			? $info['version']
			: '0.0';

		if (version_compare($this->seofilter_version, '2.9', '>') && class_exists('shopSeofilterFiltersFrontendStorage'))
		{
			$this->storage = new shopSeofilterFiltersFrontendStorage();
		}
		else
		{
			$this->storage = new shopSeofilterFiltersStorage();
		}
	}

	/**
	 * @param $storefront
	 * @param $category_id
	 * @param $filter_params
	 * @param $currency
	 * @return shopSeofilterFilter|null
	 */
	public function getByFilterParams($storefront, $category_id, $filter_params, $currency)
	{
		if (!$this->storage)
		{
			return null;
		}

		if (version_compare($this->seofilter_version, '2.9', '>') && class_exists('shopSeofilterFiltersFrontendStorage'))
		{
			return $this->storage->getByFilterParams($storefront, $category_id, $filter_params, $currency);
		}
		else
		{
			return $this->storage->getByFilter($storefront, $category_id, $filter_params, $currency);
		}
	}
}