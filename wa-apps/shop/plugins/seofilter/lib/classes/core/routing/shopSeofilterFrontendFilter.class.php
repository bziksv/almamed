<?php

/**
 * @property $params
 *
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $h1
 * @property string $description
 * @property string $additional_description
 *
 * @property string $storefront_name
 * @property string $seo_name
 *
 * @property shopSeofilterFilter $filter
 */
class shopSeofilterFrontendFilter
{
	/** @var shopSeofilterFilter */
	private $_filter;

	private $_templates = array();
	private $_currency;

	public function __construct($storefront, $category_id, shopSeofilterFilter $filter, $category_page = 1)
	{
		$this->_filter = $filter;
		$this->_currency = waRequest::param('currency', 'RUB');

		$this->_templates = $this->prepareTemplates($filter, $storefront, $category_id, $category_page);
	}

	private function prepareTemplates(shopSeofilterFilter $filter, $storefront, $category_id, $category_page)
	{
		$collector = new shopSeofilterTemplateCollector($filter, $storefront, $category_id, $category_page);

		return $collector->getTemplates();
	}

	function __get($name)
	{
		if (isset($this->_templates[$name]))
		{
			return $this->_templates[$name];
		}
		elseif ($name === 'filter')
		{
			return $this->_filter;
		}
		elseif ($name === 'params')
		{
			return $this->_filter->getFeatureValuesAsFilterParamsForCurrency($this->_currency);
		}
		elseif ($name === 'seo_name')
		{
			return $this->_filter->seo_name;
		}

		return null;
	}

	function __set($name, $value)
	{
		throw new waException('__set not implemented');
	}

	public function getTemplates()
	{
		return $this->_templates;
	}
}