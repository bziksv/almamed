<?php

class shopSeofilterRouting
{
	const ROUTING_STEP_CATEGORY = 'CATEGORY';
	const ROUTING_STEP_PRODUCT = 'PRODUCT';
	const ROUTING_STEP_SEOFILTER = 'SEOFILTER';

	private static $instance = null;

	/** @var shopSeofilterPluginSettings */
	private $settings;

	/** @var shopSeofilterFiltersStorage */
	private $filter_storage;
	/** @var shopSeofilterFiltersFrontendStorage */
	private $filter_frontend_storage;

	private $category_root_url;

	private $original_request_uri;
	private $original_url;

	private $currency;


	private $is_initialized = false;

	private $is_category_page = false;
	private $is_product_page = false;
	private $is_seofilter_page = false;

	/** @var array|null */
	private $category = null;
	/** @var shopSeofilterFilter|null */
	private $filter = null;
	/** @var shopSeofilterFrontendFilter|null */
	private $frontend_filter = null;

	private $seofilter_url_suffix;
	private $seofilter_url_suffix_is_removed = false;

	private $route = null;

	/**
	 * @return shopSeofilterRouting
	 */
	public static function instance()
	{
		if (self::$instance === null)
		{
			self::$instance = new shopSeofilterRouting();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->settings = shopSeofilterBasicSettingsModel::getSettings();

		$this->filter_storage = new shopSeofilterFiltersStorage();
		$this->filter_frontend_storage = new shopSeofilterFiltersFrontendStorage();

		$settings_root_url = $this->settings->root_url;
		$this->category_root_url = ($settings_root_url ? $settings_root_url : 'category') . '/';

		$this->is_seofilter_page = false;
		$this->is_initialized = false;

		/** @var shopConfig $shop_config */
		$shop_config = wa('shop')->getConfig();
		$this->currency = $shop_config->getCurrency(false);
	}

	private function __clone()
	{
		throw new waException("can't clone instance of [shopSeofilterRouting]");
	}

	public function getStorefront()
	{
		$routing = wa()->getRouting();
		$domain = $routing->getDomain();

		$route = $this->route === null
			? $routing->getRoute()
			: $this->route;

		return $domain . '/' . $route['url'];
	}

	public function isSeofilterPage()
	{
		return $this->is_seofilter_page;
	}

	public function isProductPage()
	{
		return $this->is_product_page;
	}

	public function isCategoryPage()
	{
		return $this->is_category_page;
	}

	public function getFilter()
	{
		return $this->filter;
	}

	public function getFrontendFilter()
	{
		return $this->frontend_filter;
	}

	public function getCategory()
	{
		return $this->category;
	}

	public function getCategoryId()
	{
		return is_array($this->category) && array_key_exists('id', $this->category)
			? $this->category['id']
			: null;
	}

	public function isInitialized()
	{
		return $this->is_initialized;
	}

	public function initializePluginRouting($url)
	{
		if ($this->is_initialized)
		{
			return;
		}

		$this->is_initialized = true;
		$this->original_request_uri = $_SERVER['REQUEST_URI'];
		$this->original_url = $url;

		try
		{
			$request_url_parser = $this->getRequestUrlParser();
		}
		catch (Exception $e)
		{
			return;
		}

		/**
		 * пытаемся понять чем является текущая страница
		 * порядок проверки по-умолчанию: категория, товар, фильтр
		 *
		 * можно изменить его настройкой routing_steps_order в файле wa-config/apps/shop/plugins/seofilter/config.php
		 */
		foreach ($this->getRoutingStepsOrder() as $step_name)
		{
			if ($step_name == self::ROUTING_STEP_CATEGORY)
			{
				$parse_result = $request_url_parser->parseAsCategoryUrl($this->original_url);

				if ($parse_result->is_category_page)
				{
					$this->category = $parse_result->category;
					$this->is_category_page = true;

					return;
				}
			}
			elseif ($step_name == self::ROUTING_STEP_PRODUCT)
			{
				$parse_result = $request_url_parser->parseAsProductUrl($this->original_url);

				if ($parse_result->is_product_page)
				{
					$this->is_product_page = true;

					return;
				}
			}
			elseif ($step_name == self::ROUTING_STEP_SEOFILTER)
			{
				$parse_result = $request_url_parser->parseAsSeofilterUrl($this->original_url);

				if ($parse_result->is_seofilter_page)
				{
					$this->filter = $parse_result->filter;
					$this->category = $parse_result->category;
					$this->seofilter_url_suffix = $parse_result->seofilter_url_suffix;

					$this->is_seofilter_page = true;

					break;
				}
			}
		}

		if (!$this->category || !$this->filter)
		{
			return;
		}

		$this->frontend_filter = new shopSeofilterFrontendFilter(
			$this->getStorefront(),
			$this->category['id'],
			$this->filter,
			waRequest::get('page', 1, waRequest::TYPE_INT)
		);
	}

	public function redispatch()
	{
		/**
		 * @var shopConfig $config
		 */
		$config = wa('shop')->getConfig();
		shopSeofilterShopConfig::cleanRoutes($config);

		shopSeofilterSystem::cleanFactories();

		wa()->getRouting()->dispatch();
		wa('shop', true)->getFrontController()->dispatch();

		exit;
	}

	/**
	 * Пустая SEO-страница фильтра с HTTP 4xx — без полного рендера категории.
	 *
	 * @throws waException
	 */
	public function respondIfEmptySeofilterPage()
	{
		if (!$this->is_seofilter_page || !$this->filter || !$this->category)
		{
			return;
		}

		if (shopSeofilterViewHelper::filterHasProducts(
			$this->filter,
			$this->getStorefront(),
			$this->category['id'],
			$this->currency
		))
		{
			return;
		}

		$http_code = $this->getEmptyPageHttpCode();
		if ((int) $http_code < 400)
		{
			return;
		}

		wa()->getResponse()->setStatus($http_code);
		throw new waException(_w('Page not found'), (int) $http_code);
	}

	private function getEmptyPageHttpCode()
	{
		$empty_page_http_code = $this->filter->empty_page_http_code;
		if (!is_string($empty_page_http_code) || strlen(trim($empty_page_http_code)) !== 3)
		{
			$empty_page_http_code = $this->settings->empty_page_http_code;
		}

		return $empty_page_http_code;
	}

	public function tryToPerformRedirects()
	{
		$redirect_url = null;

		$extra_params = shopSeofilterFilterFeatureValuesHelper::normalizeParams(waRequest::get());
		if (count($extra_params))
		{
			$filter = $this->filter_frontend_storage->getByFilterParams($this->getStorefront(), $this->category['id'], $extra_params, $this->currency);

			if ($filter)
			{
				$redirect_url = $filter->getFrontendCategoryUrlWithAdditionalParams($this->category);
			}
			else
			{
				if ($this->isSeofilterPage())
				{
					$this->removeSeofilterSuffixFromUrl();
				}

				$redirect_url = waSystem::getInstance()->getConfig()->getRequestUrl(false, true) . '?' . http_build_query($extra_params, null, '&');
			}
		}
		elseif ($this->seofilter_url_suffix !== strtolower($this->seofilter_url_suffix) || substr($this->original_url, -1, 1) != '/')
		{
			$redirect_url = $this->filter->getFrontendCategoryUrlWithAdditionalParams($this->category);
		}

		if (is_string($redirect_url) && $redirect_url !== '')
		{
			wa()->getResponse()->redirect($redirect_url, 301);
		}
	}

	public function tryRedirectToFilterPage()
	{
		$filter_params = shopSeofilterFilterFeatureValuesHelper::getGetParametersForSearch();

		$seofilter_url = new shopSeofilterFilterUrl($this->settings->url_type, waRequest::param('url_type'));

		$filter = $this->filter_frontend_storage->getByFilterParams(
			$this->getStorefront(),
			$this->category['id'],
			$filter_params,
			$this->currency
		);

		if ($filter && !$seofilter_url->haveShopUrlCollision($filter->url) && $filter->countProducts($this->category['id'], $this->currency) > 0)
		{
			wa()->getResponse()->redirect($filter->getFrontendCategoryUrlWithAdditionalParams($this->category), 301);
		}
	}

	public function removeSeofilterSuffixFromUrl()
	{
		if ($this->seofilter_url_suffix_is_removed)
		{
			return;
		}

		$this->seofilter_url_suffix_is_removed = true;

		if ($this->settings->url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			$pattern = '/' . preg_quote('-' . $this->seofilter_url_suffix, '/') . '\/?$/';
			$replace_to = '';
		}
		else
		{
			$pattern = '/' . preg_quote($this->seofilter_url_suffix, '/') . '\/?$/';
			$replace_to = '/';
		}

		$parts = array();

		$root_part = trim(wa()->getRouting()->getRootUrl(), '/');
		if (strlen($root_part))
		{
			$parts[] = $root_part;
		}

		$parts[] = trim(preg_replace($pattern, $replace_to, $this->original_url), '/');

		$_SERVER['REQUEST_URI'] = '/' . implode('/', $parts) . '/';
	}

	public function restoreInitialUrl()
	{
		$_SERVER['REQUEST_URI'] = $this->original_request_uri;
	}

	public function getOriginalRequestUri()
	{
		return $this->original_request_uri;
	}

	public function isOriginalRequestUriHasSort()
	{
		$url_parsed = parse_url($this->original_request_uri);

		if (!array_key_exists('query', $url_parsed))
		{
			return false;
		}

		parse_str($url_parsed['query'], $params);

		return array_key_exists('sort', $params) || array_key_exists('order', $params);
	}

	public function patchGetParameters()
	{
		$get_params = $this->filter->getFeatureValuesAsFilterParamsForCurrency($this->currency);

		foreach ($this->filter->featureValues as $feature_value)
		{
			$feature = $feature_value->feature;
			if ($feature && $feature->type === 'double' && $feature->selectable == '0' && $value_row = $feature_value->featureValue)
			{
				$get_params[$feature->code] = array(
					'min' => $value_row['value'],
					'max' => $value_row['value'],
				);
			}
		}

		foreach ($get_params as $param => $val)
		{
			$_GET[$param] = $val;
		}

		if (!array_key_exists('sort', $_GET))
		{
			$rule = $this->filter->getActivePersonalRule($this->getStorefront(), $this->category['id']);

			$sort = '';
			$order = '';

			if ($rule && $rule->default_product_sort)
			{
				$sort = $rule->getDefaultSortSort();
				$order = $rule->getDefaultSortOrder();
			}
			elseif ($this->filter->default_product_sort)
			{
				$sort = $this->filter->getDefaultSortSort();
				$order = $this->filter->getDefaultSortOrder();
			}
			elseif ($this->settings->default_product_sort)
			{
				$sort = $this->settings->getDefaultProductSortSort();
				$order = $this->settings->getDefaultProductSortOrder();
			}

			if ($sort && $sort != 'category')
			{
				$_GET['sort'] = $sort;

				if ($order)
				{
					$_GET['order'] = strtolower($order) == 'asc' ? 'asc' : 'desc';
				}
			}
		}
	}

	public function triggerEvent()
	{
		$filter_attributes = new shopSeofilterFilterAttributes($this->filter);

		if ($this->settings->url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			$filter_attributes->setFullUrl("{$this->category['url']}-{$this->filter->url}");
		}
		else
		{
			$filter_attributes->setFullUrl($this->seofilter_url_suffix);
		}

		$category_id = $this->category['id'];

		$storefront = $this->getStorefront();
		$canonical = $this->filter_storage->getFilterCanonical($this->filter->id, $storefront, $category_id);
		if ($canonical)
		{
			$plugin_canonical = new shopSeofilterLinkcanonicalCanonical(
				$canonical->canonical_url_template,
				$storefront,
				$this->category
			);

			$filter_attributes->setCanonicalUrlTemplate($canonical->canonical_url_template);
			$filter_attributes->setCanonicalUrl($plugin_canonical->fetchUrl());
		}

		$event_params = array(
			'filter' => $filter_attributes,
			'category_id' => $category_id,
		);
		waRequest::setParam('seofilter_filter_url', $this->seofilter_url_suffix); // todo setParam'у тут не место

		wa()->event('shop_seofilter_frontend', $event_params);
	}

	public function setRoute($route)
	{
		$this->route = $route;
	}

	private function getRequestUrlParser()
	{
		$routing = wa()->getRouting();
		$domain = $routing->getDomain();

		$route = $this->route === null
			? $routing->getRoute()
			: $this->route;

		$url_parser_factory = new shopSeofilterRequestUrlParserFactory();

		return $url_parser_factory->getFrontendUrlParser($domain, $route, $this->currency);
	}

	private function getRoutingStepsOrder()
	{
		$default_routing_steps_order = array(
			self::ROUTING_STEP_CATEGORY,
			self::ROUTING_STEP_PRODUCT,
			self::ROUTING_STEP_SEOFILTER,
		);

		if (!is_array($this->settings->routing_steps_order))
		{
			return $default_routing_steps_order;
		}

		$check_steps = array();
		foreach ($this->settings->routing_steps_order as $step_name)
		{
			$check_steps[$step_name] = true;
		}

		if (
			!array_key_exists(self::ROUTING_STEP_CATEGORY, $check_steps)
			|| !array_key_exists(self::ROUTING_STEP_PRODUCT, $check_steps)
			|| !array_key_exists(self::ROUTING_STEP_SEOFILTER, $check_steps)
		)
		{
			return $default_routing_steps_order;
		}

		return $this->settings->routing_steps_order;
	}
}
