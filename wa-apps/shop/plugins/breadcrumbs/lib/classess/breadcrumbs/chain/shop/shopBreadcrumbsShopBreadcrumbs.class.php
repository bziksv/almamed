<?php

class shopBreadcrumbsShopBreadcrumbs
{
	const ACTION_HOME = 'default';
	const ACTION_CATEGORY = 'category';
	const ACTION_PRODUCT = 'product';
	const ACTION_PRODUCT_PAGE = 'productPage';
	const ACTION_SHOP_PAGE = 'page';
	const ACTION_CART = 'cart';
	const ACTION_CHECKOUT = 'checkout';
	const ACTION_SEARCH = 'search';
	const ACTION_PRODUCT_REVIEWS = 'productReviews';

	const ACTION_REVIEWSPLUS_ADD_REVIEW_PAGE = 'addReviewPage';

	const ACTION_MY = 'my';
	const ACTION_MY_PROFILE = 'myProfile';
	const ACTION_MY_ORDERS = 'myOrders';
	const ACTION_MY_AFFILIATE = 'myAffiliate';
	const ACTION_MY_ORDER = 'myOrder';
	const ACTION_MY_ORDERBYCODE = 'myOrderByCode';
	const ACTION_MY_ORDERDOWNLOAD = 'myOrderDownload';
	const ACTION_MY_ORDERPRINTFORM = 'myOrderPrintform';

	const PLUGIN_PRODUCTBRANDS = 'productbrands';
	const PLUGIN_SEOBRAND = 'seobrand';
	const PLUGIN_BRAND = 'brand';
	const PLUGIN_REVIEWSPLUS = 'reviewsplus';
	const PLUGIN_SEARCHPRO = 'searchpro';
	const PLUGIN_CATALOGREVIEWS = 'catalogreviews';

	const BRAND_ACTION_BRANDS_PAGE = 'brands';
	const BRAND_ACTION_BRAND_PAGE = 'brandPage';
	const BRAND_ACTION_ADD_BRAND_REVIEW = 'addReviewPage';

	/**
	 * @var shopBreadcrumbsSettings
	 */
	private $settings;

	private $home_name;

	private $helper;

	public function __construct(shopBreadcrumbsSettings $settings, $home_name)
	{
		$this->settings = $settings;
		$this->home_name = $home_name;

		$this->helper = new shopBreadcrumbsHelper();
	}

	/**
	 * @return shopBreadcrumbsBreadcrumbsChain
	 */
	public function getChain()
	{
		$plugin = shopBreadcrumbsPlugin::param('plugin');
		$action = shopBreadcrumbsPlugin::param('action');

		$settings = $this->settings;

		$element_collector = new shopBreadcrumbsShopBreadcrumbsElementCollector($settings);

		$pages_without_breadcrumbs = $this->pagesWithoutBreadcrumbs();
		if (isset($pages_without_breadcrumbs[$action]))
		{
			return $element_collector->getChain();
		}

		$element_collector->addHome($this->home_name);

		$need_custom = false;

		if ($action === self::ACTION_HOME)
		{
		}
		elseif ($action === self::ACTION_CATEGORY)
		{
			$element_collector->addCategories();
		}
		elseif ($action === self::ACTION_PRODUCT)
		{
			$element_collector->addProductCategories();

			if ($settings->add_seofilter_items)
			{
				$element_collector->addSeofilter();
			}

			$element_collector->addProduct();
		}
		elseif ($action === self::ACTION_PRODUCT_PAGE)
		{
			$element_collector->addProductCategories();

			if ($settings->add_seofilter_items)
			{
				$element_collector->addSeofilter();
			}

			$element_collector->addProduct()->addProductPage();
		}
		elseif ($plugin === self::PLUGIN_SEARCHPRO && $action === self::ACTION_SHOP_PAGE)
		{
			$element_collector->addSearchpro();
		}
		elseif ($plugin === self::PLUGIN_CATALOGREVIEWS)
		{
			$element_collector
				->addCategories()
				->addCatalogreviews();
		}
		elseif ($action === self::ACTION_SHOP_PAGE)
		{
			$element_collector->addShopPages();
		}
		elseif ($action === self::ACTION_CART)
		{
			$element_collector->addCart();
		}
		elseif ($action === self::ACTION_CHECKOUT)
		{
			$element_collector->addCheckout();
		}
		elseif ($action === self::ACTION_SEARCH)
		{
			$element_collector->addSearch();
		}
		elseif ($action === self::ACTION_PRODUCT_REVIEWS)
		{
			$element_collector->addProductCategories();

			if ($settings->add_seofilter_items)
			{
				$element_collector->addSeofilter();
			}

			$element_collector->addProduct()->addProductReviews();
		}
		elseif ($plugin === self::PLUGIN_PRODUCTBRANDS)
		{
			$element_collector->addProductbrands();
		}
		elseif ($plugin === self::PLUGIN_SEOBRAND)
		{
			$element_collector->addSeobrandElements();
		}
		elseif ($plugin === self::PLUGIN_BRAND)
		{
			if ($this->helper->isBrandPluginEnabled())
			{
				$element_collector->addBrandElements();
			}
		}
		elseif ($plugin === self::PLUGIN_REVIEWSPLUS)
		{
			if ($this->helper->isReviewsplusEnabled())
			{
				if ($action === self::ACTION_REVIEWSPLUS_ADD_REVIEW_PAGE)
				{
					$element_collector->addProductCategories();

					if ($settings->add_seofilter_items)
					{
						$element_collector->addSeofilter();
					}

					$element_collector->addProduct();
				}
			}
		}
		else
		{
			$need_custom = true;
		}
		
		$element_collector->getHookHandlerElement();
		
		if ($need_custom)
		{
			$element_collector->addCustom();
		}

		return $element_collector->getChain();
	}

	private function pagesWithoutBreadcrumbs()
	{
		return array(
			self::ACTION_MY => self::ACTION_MY,
			self::ACTION_MY_AFFILIATE => self::ACTION_MY_AFFILIATE,
			self::ACTION_MY_ORDER => self::ACTION_MY_ORDER,
			self::ACTION_MY_ORDERBYCODE => self::ACTION_MY_ORDERBYCODE,
			self::ACTION_MY_ORDERDOWNLOAD => self::ACTION_MY_ORDERDOWNLOAD,
			self::ACTION_MY_ORDERPRINTFORM => self::ACTION_MY_ORDERPRINTFORM,
			self::ACTION_MY_ORDERS => self::ACTION_MY_ORDERS,
			self::ACTION_MY_PROFILE => self::ACTION_MY_PROFILE,
		);
	}
}