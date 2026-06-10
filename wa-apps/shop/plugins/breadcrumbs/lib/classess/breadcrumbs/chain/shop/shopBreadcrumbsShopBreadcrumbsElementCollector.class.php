<?php

class shopBreadcrumbsShopBreadcrumbsElementCollector
{
	private static $product = null;
	private static $category = null;
	private static $product_page = null;
	private static $shop_page = null;
	private static $breadcrumbs = null;
	private static $brand = null;
	private static $seobrand = null;
	private static $seobrand_page = null;

	private $settings;

	private $chain;
	private $element_factory;

	public function __construct(shopBreadcrumbsSettings $settings)
	{
		$this->settings = $settings;

		$this->chain = new shopBreadcrumbsBreadcrumbsChain();
		$this->element_factory = new shopBreadcrumbsShopBreadcrumbsElementFactory();
	}

	public function getChain()
	{
		return $this->chain;
	}

	public function addHome($home_name)
	{
		$this->chain->add($this->element_factory->home($home_name));

		return $this;
	}

	public function addCategories()
	{
		$category = $this->getCategory();
		$category_element = $this->settings->show_subcategories
			? $this->element_factory->categoryWithBrothers($category, $this->settings->category_name_mode)
			: $this->element_factory->category($category, $this->settings->category_name_mode);

		$this->chain->add($category_element);

		return $this;
	}

	public function addProductCategories()
	{
		// todo сделать как в shopProduct::getCanonicalCategory
		$product = $this->getProduct();
		$category_id = $product['category_id'];

		$category_element = $this->settings->show_subcategories
			? $this->element_factory->categoryWithBrothers($category_id, $this->settings->category_name_mode)
			: $this->element_factory->category($category_id, $this->settings->category_name_mode);

		$this->chain->add($category_element);

		return $this;
	}

	public function addSeofilter()
	{
		$this->chain->add($this->element_factory->seofilter(
			$this->getProduct(),
			$this->settings->seofilter_feature_ids,
			$this->settings->product_seofilter_item_mode
		));

		return $this;
	}

	public function addProduct()
	{
		$this->chain->add($this->element_factory->product($this->getProduct()));

		return $this;
	}

	public function addProductPage()
	{
		$this->chain->add($this->element_factory->productPage($this->getProduct(), $this->getProductPage()));

		return $this;
	}

	public function addShopPages()
	{
		$this->chain->add($this->element_factory->shopPage($this->getShopPage()));

		return $this;
	}

	public function addCart()
	{
		$this->chain->add($this->element_factory->cart());

		return $this;
	}

	public function addCheckout()
	{
		$this->chain->add($this->element_factory->checkout());

		return $this;
	}

	public function addSearch()
	{
		$this->chain->add($this->element_factory->search());

		return $this;
	}

	public function addProductReviews()
	{
		$this->chain->add($this->element_factory->productReviews($this->getProduct()));

		return $this;
	}

	public function addProductbrands()
	{
		$this->chain->add($this->element_factory->productbrands($this->getProductbrand()));

		return $this;
	}

	public function addSeobrandElements()
	{
		$this->chain->add($this->element_factory->seobrand($this->getSeobrand(), $this->getSeobrandPage()));

		return $this;
	}

	public function addBrandElements()
	{
		$this->chain->add($this->element_factory->brand($this->getBrandBrand(), $this->getBrandPage()));

		return $this;
	}

	public function addSearchpro()
	{
		$this->chain->add($this->element_factory->searchpro());

		return $this;
	}

	public function addCatalogreviews()
	{
		$this->chain->add($this->element_factory->catalogreviews());

		return $this;
	}

	public function addReviewsplusAddReviewPageElement()
	{
		$title = $this->getReviewsplusTitle();

		$this->chain->add($this->element_factory->reviewsplusAddReviewPage($title));

		return $this;
	}

	public function addCustom()
	{
		$view_breadcrumbs = $this->getViewBreadcrumbs();

		if (is_array($view_breadcrumbs) && count($view_breadcrumbs))
		{
			$this->chain->add($this->element_factory->customElement(array_slice($view_breadcrumbs, -1)));
		}

		return $this;
	}

	public function getHookHandlerElement()
	{
		$plugin = shopBreadcrumbsPlugin::param('plugin');
		$action = shopBreadcrumbsPlugin::param('action');

		$page = $plugin ? $plugin . '_' . $action : $action;
		
		$extra_breadcrumbs = shopBreadcrumbsPlugin::getExtraBreadcrumbs($page);
		
		$this->chain->add($this->element_factory->customElement($extra_breadcrumbs));

		if (shopBreadcrumbsPlugin::overrideCurrent($page))
		{
			self::$breadcrumbs = array();
		}

		return $this;
	}

	private function getProduct()
	{
		$product_model = new shopProductModel();

		$product_url = waRequest::param('product_url');
		$category_url = waRequest::param('category_url');

		$product = null;

		if (waRequest::param('url_type') == 2 && $category_url)
		{
			$category_model = new shopCategoryModel();
			$category = $category_model->getByField('full_url', $category_url);

			if ($category)
			{
				$sql = '
SELECT p.*
FROM shop_product AS p
	JOIN shop_category_products AS cp
		ON cp.product_id = p.id AND cp.category_id = :category_id
WHERE p.url = :product_url
';

				$query_params = array(
					'category_id' => $category['id'],
					'product_url' => $product_url,
				);

				$product = $product_model->query($sql, $query_params)->fetchAssoc();
			}
		}
		else
		{
			$product = $product_model->getByField('url', $product_url);
		}

		return $product
			? $product
			: null;
	}

	private function getCategory()
	{
		if (self::$category === null)
		{
			self::$category = wa()->getView()->getVars('category');
		}

		return self::$category;
	}

	private function getProductPage()
	{
		if (self::$product_page === null)
		{
			self::$product_page = wa()->getView()->getVars('page');
		}

		return self::$product_page;
	}

	private function getShopPage()
	{
		if (self::$shop_page === null)
		{
			self::$shop_page = wa()->getView()->getVars('page');
		}

		return self::$shop_page;
	}

	private function getViewBreadcrumbs()
	{
		if (self::$breadcrumbs === null)
		{
			self::$breadcrumbs = wa()->getView()->getVars('breadcrumbs');
		}

		return self::$breadcrumbs;
	}

	private function getProductbrand()
	{
		if (self::$brand === null)
		{
			self::$brand = wa()->getView()->getVars('brand');
		}

		return self::$brand;
	}

	private function getSeobrand()
	{
		if (self::$seobrand === null)
		{
			self::$seobrand = wa()->getView()->getVars('brand');
		}

		return self::$seobrand;
	}

	private function getSeobrandPage()
	{
		if (self::$seobrand_page === null)
		{
			self::$seobrand_page = wa()->getView()->getVars('brand_page');
		}

		return self::$seobrand_page;
	}

	/**
	 * @return shopBrandBrand
	 */
	private function getBrandBrand()
	{
		return waRequest::param('brand_plugin_brand');
	}

	/**
	 * @return shopBrandPage
	 */
	private function getBrandPage()
	{
		return waRequest::param('brand_plugin_page');
	}

	/**
	 * @return string
	 */
	private function getReviewsplusTitle()
	{
		return wa()->getView()->getVars('title');
	}
}