<?php

class shopSeofilterRequestUrlParser
{
	private $domain;
	private $route;
	private $currency;
	private $settings;
	private $tree_settings;
	private $filter_storage;

	private $category_model;
	private $product_model;

	private $product_root_url = 'product' . '/';
	private $category_root_url = 'category' . '/';
	private $simple_filter_url_root = 'filter' . '/';

	public function __construct(
		$domain,
		$route,
		$currency,
		shopSeofilterPluginSettings $settings,
		shopSeofilterFilterTreeSettings $tree_settings,
		shopSeofilterFiltersStorage $filter_storage
	)
	{
		$this->domain = $domain;
		$this->route = $route;
		$this->currency = $currency;
		$this->settings = $settings;
		$this->tree_settings = $tree_settings;
		$this->filter_storage = $filter_storage;

		$this->category_model = new shopCategoryModel();
		$this->product_model = new shopProductModel();

		if ($settings->root_url)
		{
			$this->category_root_url = $settings->root_url . '/';
		}
	}

	public function parseAsProductUrl($request_url)
	{
		$result_builder = new shopSeofilterRequestUrlParseResultBuilder();

		try
		{
			$product = $this->tryGetProductByUrl($request_url);

			$result_builder
				->setIsProductPage(true)
				->setProduct($product);
		}
		catch (waException $e)
		{
		}

		return $result_builder->build();
	}

	public function parseAsCategoryUrl($request_url)
	{
		$result_builder = new shopSeofilterRequestUrlParseResultBuilder();

		try
		{
			$category = $this->tryGetCategoryByUrl($request_url);

			$result_builder
				->setIsCategoryPage(true)
				->setCategory($category);
		}
		catch (waException $e)
		{
		}

		return $result_builder->build();
	}

	/**
	 * @param string $request_url
	 * @return shopSeofilterRequestUrlParseResult
	 */
	public function parseAsSeofilterUrl($request_url)
	{
		$result_builder = new shopSeofilterRequestUrlParseResultBuilder();

		try
		{
			/** @var shopSeofilterFilter $filter */
			list($category, $filter, $seofilter_url_suffix) = $this->tryGetCategoryAndFilterByUrl($request_url);

			$result_builder
				->setIsSeofilterPage(true)
				->setCategory($category)
				->setFilter($filter)
				->setSeofilterUrlSuffix($seofilter_url_suffix);
		}
		catch (waException $e)
		{
		}

		return $result_builder->build();
	}

	/**
	 * @param $url
	 * @return array
	 * @throws waException
	 */
	private function tryGetProductByUrl($url)
	{
		/**
		 * product
		 * 0: <product>/
		 * 1: product/<product>/
		 * 2: <cat>/<sub_cat>/<product>/
		 */
		$product = null;

		$route_url_type = $route_url_type = ifset($this->route, 'url_type', shopSeofilterWaShopUrlType::MIXED);

		if ($route_url_type == shopSeofilterWaShopUrlType::MIXED)
		{
			$product_url = trim($url, '/');

			$product = $this->product_model->getByUrl($product_url);
		}
		elseif ($route_url_type == shopSeofilterWaShopUrlType::PLAIN)
		{
			if (strpos($url, $this->product_root_url) !== 0)
			{
				throw new waException("invalid product url [{$url}]");
			}

			$product_url = substr($url, strlen($this->product_root_url));
			$product_url = trim($product_url, '/');

			$product = $this->product_model->getByUrl($product_url);
		}
		elseif ($route_url_type == shopSeofilterWaShopUrlType::NATURAL)
		{
			$url_trimmed = trim($url, '/');
			$url_parts = explode('/', $url_trimmed);

			if (count($url_parts) == 0)
			{
				throw new waException("invalid product url [{$url}]");
			}

			if (count($url_parts) > 1)
			{
				$category_url = implode('/', array_slice($url_parts, 0, -1));

				try
				{
					$category = $this->tryGetCategoryByUrl($category_url);
				}
				catch (waException $e)
				{
					throw new waException("invalid product url [{$url}]");
				}
			}

			$product_url = $url_parts[count($url_parts) - 1];
			$product = $this->product_model->getByUrl($product_url);
		}
		else
		{
			throw new waException("invalid route url type [{$route_url_type}]");
		}

		if (!$product)
		{
			throw new waException("Can not find product with url [{$product_url}]");
		}

		return $product;
	}

	/**
	 * @param $url
	 * @return array
	 * @throws waException
	 */
	private function tryGetCategoryAndFilterByUrl($url)
	{
		$found_category = null;
		$found_filter = null;
		$found_filter_url_suffix = null;

		$storefront = $this->getStorefront();

		$possible_partitions = $this->getPossibleUrlPartitions($url);
		foreach ($possible_partitions as $possible_partition)
		{
			$found_category = null;
			$found_filter = null;
			$found_filter_url_suffix = null;

			list($category_url, $seofilter_url, $found_filter_url_suffix) = $possible_partition;

			try
			{
				$found_category = $this->tryGetCategoryByUrl($category_url);
			}
			catch (waException $e)
			{
				continue;
			}

			$category_id = $found_category['id'];


			if (!$this->tree_settings->isPluginEnabledOnStorefrontCategory($storefront, $category_id))
			{
				continue;
			}

			$found_filter = $this->filter_storage->getByUrl(
				$storefront,
				$category_id,
				$seofilter_url
			);

			if (!$found_filter)
			{
				continue;
			}

			break;
		}


		if (!$found_filter || !$found_category)
		{
			throw new waException("can't find filter on url [{$url}] for storefront [{$storefront}]");
		}


		if ($this->settings->consider_category_filters)
		{
			$validator = new shopSeofilterFilterFeaturesValidator();

			$filter_get_params = $found_filter->getFeatureValuesAsFilterParamsForCurrency($this->currency);
			if (!$validator->validateCategoryParams($found_category['id'], $filter_get_params))
			{
				throw new waException("filter [{$found_filter->id}] is not enabled for category [{$found_category['id']}] due category filtration settings");
			}
		}

		if (!$this->tree_settings->isFilterEnabled($storefront, $found_category['id'], $found_filter))
		{
			throw new waException("filter [{$found_filter->id}] is not enabled for storefront [{$storefront}] and category id [{$found_category['id']}] due storefront category tree settings");
		}

		if ($found_filter->hasDeletedFeatureValues())
		{
			throw new waException("filter [{$found_filter->id}] has deleted feature or feature value");
		}

		return array($found_category, $found_filter, $found_filter_url_suffix);
	}

	private function getStorefront()
	{
		return $this->domain . '/' . ifset($this->route, 'url', '');
	}

	/**
	 * @param $url
	 * @return array
	 * @throws waException
	 */
	private function getPossibleUrlPartitions($url)
	{
		$url_trimmed = $url;
		if (substr($url, -1, 1) == '/')
		{
			$url_trimmed = substr($url, 0, -1);
		}

		$possible_partitions = array();

		$url_parts = explode('/', $url_trimmed);

		$seofilter_url_type = $this->settings->url_type;
		if ($seofilter_url_type == shopSeofilterPluginUrlType::SHORT)
		{
			if (count($url_parts) > 1)
			{
				$category_url = implode('/', array_slice($url_parts, 0, -1));
				$seofilter_url = $url_parts[count($url_parts) - 1];
				$seofilter_url_suffix = $seofilter_url;

				$possible_partitions[] = array($category_url, $seofilter_url, $seofilter_url_suffix);
			}
		}
		elseif ($seofilter_url_type == shopSeofilterPluginUrlType::SIMPLE)
		{
			if (count($url_parts) > 2 && $url_parts[count($url_parts) - 2] === trim($this->simple_filter_url_root, '/'))
			{
				$category_url = implode('/', array_slice($url_parts, 0, -2));
				$seofilter_url = $url_parts[count($url_parts) - 1];
				$seofilter_url_suffix = implode('/', array_slice($url_parts, -2));

				$possible_partitions[] = array($category_url, $seofilter_url, $seofilter_url_suffix);
			}
		}
		elseif ($seofilter_url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			$category_url_prefix = count($url_parts) > 1
				? implode('/', array_slice($url_parts, 0, -1))
				: '';
			$joined_url = $url_parts[count($url_parts) - 1];

			$joined_url_parts = explode('-', $joined_url);
			if (count($joined_url_parts) > 1)
			{
				for ($category_part_length = 1; $category_part_length < count($joined_url_parts); $category_part_length++)
				{
					$category_url_suffix = implode('-', array_slice($joined_url_parts, 0, $category_part_length));

					$category_url = $category_url_prefix === ''
						? $category_url_suffix
						: $category_url_prefix . '/' . $category_url_suffix;
					$seofilter_url = implode('-', array_slice($joined_url_parts, $category_part_length));
					$seofilter_url_suffix = $seofilter_url;

					$possible_partitions[] = array($category_url, $seofilter_url, $seofilter_url_suffix);
				}
			}
		}
		else
		{
			throw new waException("unknown seofilter url type [{$seofilter_url_type}]");
		}

		if (count($possible_partitions) === 0)
		{
			throw new waException("url [{$url}] can't be seofilter url");
		}

		return $possible_partitions;
	}

	/**
	 * @param $url
	 * @return null|array
	 * @throws waException
	 */
	private function tryGetCategoryByUrl($url)
	{
		/**
		 *
		 * category
		 * 0: category/<cat>/<sub_cat>/
		 * 1: category/<sub_cat>/
		 * 2: <cat>/<sub_cat>/
		 */

		$category = null;

		$route_url_type = ifset($this->route, 'url_type', shopSeofilterWaShopUrlType::MIXED);

		if ($route_url_type == shopSeofilterWaShopUrlType::MIXED)
		{
			if (strpos($url, $this->category_root_url) !== 0)
			{
				throw new waException("Invalid category url {$url}");
			}

			$category_full_url = substr($url, strlen($this->category_root_url));
			$category_full_url = trim($category_full_url, '/');
			$category = $this->category_model->getByField('full_url', $category_full_url);
		}
		elseif ($route_url_type == shopSeofilterWaShopUrlType::PLAIN)
		{
			if (strpos($url, $this->category_root_url) !== 0)
			{
				throw new waException("Invalid category url {$url}");
			}

			$category_url = substr($url, strlen($this->category_root_url));
			$category_url = trim($category_url, '/');
			$category = $this->category_model->getByField('url', $category_url);
		}
		elseif ($route_url_type == shopSeofilterWaShopUrlType::NATURAL)
		{
			$category_full_url = trim($url, '/');
			$category = $this->category_model->getByField('full_url', $category_full_url);
		}

		if (!$category)
		{
			throw new waException("Can not find category by url {$url}");
		}

		return $category;
	}
}