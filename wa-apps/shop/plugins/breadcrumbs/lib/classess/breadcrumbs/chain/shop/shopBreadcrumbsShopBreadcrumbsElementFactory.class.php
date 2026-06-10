<?php

class shopBreadcrumbsShopBreadcrumbsElementFactory
{
	//private $storefront;

	//public function __construct($storefront)
	private $category_model;
	private $product_model;
	private $product_page_model;
	private $shop_page_model;
	private $feature_model;

	public function __construct()
	{
		//$this->storefront = $storefront;

		$this->category_model = new shopCategoryModel();
		$this->product_model = new shopProductModel();
		$this->product_page_model  = new shopProductPagesModel();
		$this->shop_page_model  = new shopPageModel();
		$this->feature_model  = new shopFeatureModel();
	}

	public function home($home_name)
	{
		return new shopBreadcrumbsHomeBreadcrumbsElement($home_name);
	}

	public function category($last_category, $category_name_mode)
	{
		return new shopBreadcrumbsCategoryBreadcrumbsElement(
			$this->prepareCategory($last_category),
			$category_name_mode
		);
	}

	public function categoryWithBrothers($last_category, $category_name_mode)
	{
		return new shopBreadcrumbsCategoryWithBrothersBreadcrumbsElement(
			$this->prepareCategory($last_category),
			$category_name_mode
		);
	}

	public function product($product)
	{
		return new shopBreadcrumbsProductBreadcrumbsElement(
			$this->prepareProduct($product)
		);
	}

	public function seofilter($product, $features, $product_seofilter_item_mode)
	{
		return new shopBreadcrumbsSeofilterBreadcrumbsElement(
			$this->prepareProduct($product),
			$this->prepareFeatures($features),
			$product_seofilter_item_mode
		);
	}

	public function productPage($product, $product_page)
	{
		return new shopBreadcrumbsProductPageBreadcrumbsElement(
			$this->prepareProduct($product),
			$this->prepareProductPage($product_page)
		);
	}

	public function shopPage($page)
	{
		return new shopBreadcrumbsShopPageBreadcrumbsElement(
			$this->prepareShopPage($page)
		);
	}

	public function cart()
	{
		return new shopBreadcrumbsCartBreadcrumbsElement();
	}

	public function checkout()
	{
		return new shopBreadcrumbsCheckoutBreadcrumbsElement();
	}

	public function search()
	{
		return new shopBreadcrumbsSearchBreadcrumbsElement();
	}

	public function productReviews($product)
	{
		return new shopBreadcrumbsProductReviewsBreadcrumbsElement(
			$this->prepareProduct($product)
		);
	}

	public function customElement($custom_breadcrumbs)
	{
		return new shopBreadcrumbsCustomBreadcrumbsElement($custom_breadcrumbs);
	}

	public function productbrands($brand)
	{
		return new shopBreadcrumbsProductbrandsBreadcrumbsElement($brand);
	}

	public function seobrand($brand, $brand_page)
	{
		return new shopBreadcrumbsSeobrandBreadcrumbsElement($brand, $brand_page);
	}

	public function brand($brand, $page)
	{
		return new shopBreadcrumbsBrandBreadcrumbsElement($brand, $page);
	}

	public function searchpro()
	{
		return new shopBreadcrumbsSearchproBreadcrumbsElement();
	}

	public function catalogreviews()
	{
		return new shopBreadcrumbsCatalogreviewsBreadcrumbsElement();
	}

	public function reviewsplusAddReviewPage($title)
	{
		return new shopBreadcrumbsReviewsplusAddReviewElement($title);
	}

	private function prepareCategory($category)
	{
		return wa_is_int($category)
			? $this->category_model->getById($category)
			: $category;
	}

	private function prepareProduct($product)
	{
		if ($product instanceof shopProduct)
		{
			return $product;
		}
		elseif (is_array($product))
		{
			return new shopProduct($product);
		}
		elseif (wa_is_int($product))
		{
			$product_id = $product;
			$product = $this->product_model->getById($product_id);

			return new shopProduct($product);
		}
		else
		{
			return $product;
		}
	}

	private function prepareProductPage($product_page)
	{
		return wa_is_int($product_page)
			? $this->product_page_model->getById($product_page)
			: $product_page;
	}

	private function prepareShopPage($shop_page)
	{
		return wa_is_int($shop_page)
			? $this->shop_page_model->getById($shop_page)
			: $shop_page;
	}

	private function prepareFeatures($features)
	{
		foreach ($features as $index => $feature)
		{
			if (wa_is_int($feature))
			{
				$features[$index] = $this->feature_model->getById($feature);
			}
		}

		return $features;
	}
}