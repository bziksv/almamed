<?php

class shopSeofilterSitemapCacheGenerator
{
	private $queue_item;

	private $category;
	private $route;

	private $valid_filter_ids;
	private $invalid_filter_ids;

	private $filter_to_check_ids;
	private $single_value_filter_ids;

	private $filter_model;

	private $consider_category_filters = false;
	private $filter_features_validator;

	private $preserved_shop_environment = array();

	private $tree_settings;

	public function __construct($queue_item)
	{
		$this->queue_item = $queue_item;

		$this->category = $this->getQueueItemCategory();
		$this->route = $this->getQueueItemRoute();

		if (!($this->category && $this->route))
		{
			throw new waException('invalid queue item');
		}

		$this->filter_model = new shopSeofilterFilterModel();

		$this->filter_to_check_ids = $queue_item['filter_ids'];
		$this->single_value_filter_ids = $queue_item['filter_ids_with_single_value'];

		$this->valid_filter_ids = array();
		$this->invalid_filter_ids = array();

		$this->filter_features_validator = new shopSeofilterFilterFeaturesValidator();

		$this->tree_settings = new shopSeofilterFilterTreeSettings();
	}

	public function step()
	{
		$start_at = microtime(true);

		$this->prepareShopAppEnvironment();

		if ($this->isPluginEnabledForQueueItem())
		{
			if (count($this->single_value_filter_ids))
			{
				$this->processSingleValueFilters();
			}
			elseif (count($this->filter_to_check_ids))
			{
				$this->processQueueItemFilter((int) array_shift($this->filter_to_check_ids));
			}
		}
		else
		{
			$this->setAllInvalid();
		}

		$this->restoreShopAppEnvironment();

		return microtime(true) - $start_at;
	}

	public function haveFiltersToCheck()
	{
		return count($this->single_value_filter_ids) > 0 || count($this->filter_to_check_ids) > 0;
	}

	public function getRemainingFilterIds()
	{
		return $this->filter_to_check_ids;
	}

	public function getQueueItem()
	{
		return $this->queue_item;
	}

	public function getCacheData()
	{
		return array(
			'storefront' => $this->route['storefront'],
			'category_id' => $this->category['id'],
			'valid_filter_ids' => $this->valid_filter_ids,
			'invalid_filter_ids' => $this->invalid_filter_ids,
		);
	}

	public function keepCategoryFilterFiltersOnly()
	{
		$this->consider_category_filters = true;
	}

	private function processQueueItemFilter($filter_id)
	{
		$ar = new shopSeofilterFilter();

		$filter = $ar->getById($filter_id);

		if ($filter && $this->checkFilter($filter))
		{
			$this->valid_filter_ids[$filter_id] = $filter_id;
		}
		else
		{
			$this->invalid_filter_ids[$filter_id] = $filter_id;
		}
	}

	private function getQueueItemCategory()
	{
		$model = new shopCategoryModel();

		return $model->getById($this->queue_item['category_id']);
	}

	private function getQueueItemRoute()
	{
		$routing = wa('shop')->getRouting();

		$domain = $routing->getDomain($this->queue_item['domain']);

		$routes = $routing->getByApp('shop', $domain);

		$shop_route_url = $this->queue_item['shop_url'];
		$queue_item_route = null;
		foreach ($routes as $route)
		{
			if ($route['url'] === $shop_route_url)
			{
				$queue_item_route = $route;
				break;
			}
		}

		if ($queue_item_route === null)
		{
			return false;
		}

		$queue_item_route['storefront'] = $this->queue_item['storefront'];
		$queue_item_route['domain'] = $domain;
		$queue_item_route['in_stock_only'] = isset($queue_item_route['drop_out_of_stock'])
			? $queue_item_route['drop_out_of_stock'] == 2
			: null;
		$queue_item_route['url_type'] = ifset($queue_item_route['url_type']);

		return $queue_item_route;
	}

	private function checkFilter(shopSeofilterFilter $filter)
	{
		$storefront = $this->route['storefront'];
		$category_id = $this->category['id'];
		$currency = ifset($this->route['currency'], 'RUB');

		if (
			$filter->isAppliedToStorefrontCategory($storefront, $category_id)
			&& $this->tree_settings->isFilterEnabled($storefront, $category_id, $filter)
		)
		{
			$filter_get_params = $filter->getFeatureValuesAsFilterParamsForCurrency($currency);

			if (
				$this->consider_category_filters
				&& !$this->filter_features_validator->validateCategoryParams($this->category['id'], $filter_get_params)
			)
			{
				return false;
			}

			$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);
			$collection->filterStorefront($storefront);

			$collection->filters($filter_get_params);
			$collection->addWhere('status = 1');

			$filter_count = $collection->count();
		}
		else
		{
			$filter_count = 0;
		}

		return $filter_count > 0;
	}

	private function processSingleValueFilters()
	{
		$storefront = $this->route['storefront'];
		$category_id = $this->category['id'];
		$currency = ifset($this->route['currency'], 'RUB');

		$filters_feature_values = array();

		$filter_ar = new shopSeofilterFilter();
		foreach ($this->single_value_filter_ids as $filter_id)
		{
			$filter = $filter_ar->getById($filter_id);

			if (
				$filter
				&& $filter->isAppliedToStorefrontCategory($storefront, $category_id)
				&& $this->tree_settings->isFilterEnabled($storefront, $category_id, $filter)
			)
			{
				if ($this->consider_category_filters)
				{
					$filter_get_params = $filter->getFeatureValuesAsFilterParamsForCurrency($currency);

					if (!$this->filter_features_validator->validateCategoryParams($this->category['id'], $filter_get_params))
					{
						$this->invalid_filter_ids[$filter_id] = $filter_id;

						continue;
					}
				}

				foreach ($filter->featureValues as $feature_value)
				{
					$filters_feature_values[$filter->id] = array(
						'feature_id' => $feature_value->feature_id,
						'value_id' => $feature_value->value_id,
					);
				}
			}
			else
			{
				$this->invalid_filter_ids[$filter_id] = $filter_id;
			}
		}
		unset($filter);

		if (count($filters_feature_values))
		{
			$collection = shopSeofilterProductsCollectionFactory::getCollection('category/' . $category_id);

			if (isset($this->route['drop_out_of_stock']) && $this->route['drop_out_of_stock'] == '2')
			{
				$collection->filters(array('in_stock_only' => true));
			}

			$collection->addWhere('status = 1');
			$collection->filterStorefront($storefront);

			$feature_value_ids = $collection->getFeatureValueIds();

			foreach ($filters_feature_values as $filter_id => $feature_value)
			{
				if (isset($feature_value_ids[$feature_value['feature_id']]) && is_array($feature_value_ids[$feature_value['feature_id']]) && in_array($feature_value['value_id'], $feature_value_ids[$feature_value['feature_id']]))
				{
					$this->valid_filter_ids[$filter_id] = $filter_id;
				}
				else
				{
					$this->invalid_filter_ids[$filter_id] = $filter_id;
				}
			}
		}

		$this->single_value_filter_ids = array();
	}

	private function prepareShopAppEnvironment()
	{
		$this->preserved_shop_environment = array(
			'type_id' => waRequest::param('type_id'),
			'drop_out_of_stock' => waRequest::param('drop_out_of_stock'),
			'currency_param' => waRequest::param('currency'),
			'currency_storage' => wa()->getStorage()->get('shop/currency'),
		);

		$currency = ifset($this->route['currency'], 'RUB');
		waRequest::setParam('type_id', ifset($this->route['type_id'], '0'));
		waRequest::setParam('drop_out_of_stock', ifset($this->route['drop_out_of_stock']));
		waRequest::setParam('currency', $currency);
		wa()->getStorage()->set('shop/currency', $currency);
	}

	private function restoreShopAppEnvironment()
	{
		if (!array_key_exists('type_id', $this->preserved_shop_environment))
		{
			return;
		}

		waRequest::setParam('type_id', $this->preserved_shop_environment['type_id']);
		waRequest::setParam('drop_out_of_stock', $this->preserved_shop_environment['drop_out_of_stock']);
		waRequest::setParam('currency', $this->preserved_shop_environment['currency_param']);
		wa()->getStorage()->set('shop/currency', $this->preserved_shop_environment['currency_storage']);

		$this->preserved_shop_environment = array();
	}

	private function isPluginEnabledForQueueItem()
	{
		return $this->tree_settings->isPluginEnabledOnStorefrontCategory($this->route['storefront'], $this->category['id']);
	}

	private function setAllInvalid()
	{
		foreach ($this->single_value_filter_ids as $filter_id)
		{
			$this->invalid_filter_ids[$filter_id] = $filter_id;
		}

		foreach ($this->filter_to_check_ids as $filter_id)
		{
			$this->invalid_filter_ids[$filter_id] = $filter_id;
		}

		$this->single_value_filter_ids = array();
		$this->filter_to_check_ids = array();
	}
}
