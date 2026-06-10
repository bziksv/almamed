<?php

class shopSeofilterRequestUrlParseResultBuilder
{
	private $is_product_page = false;
	private $is_category_page = false;
	private $is_seofilter_page = false;

	private $category = null;
	private $product = null;
	/** @var shopSeofilterFilter|null */
	private $filter = null;
	private $seofilter_url_suffix = null;

	public function build()
	{
		return new shopSeofilterRequestUrlParseResult(
			$this->is_product_page,
			$this->is_category_page,
			$this->is_seofilter_page,
			$this->category,
			$this->product,
			$this->filter,
			$this->seofilter_url_suffix
		);
	}

	/**
	 * @param bool $is_product_page
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setIsProductPage($is_product_page)
	{
		$this->is_product_page = $is_product_page;

		return $this;
	}

	/**
	 * @param bool $is_category_page
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setIsCategoryPage($is_category_page)
	{
		$this->is_category_page = $is_category_page;

		return $this;
	}

	/**
	 * @param bool $is_seofilter_page
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setIsSeofilterPage($is_seofilter_page)
	{
		$this->is_seofilter_page = $is_seofilter_page;

		return $this;
	}

	/**
	 * @param array $category
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setCategory($category)
	{
		$this->category = $category;

		return $this;
	}

	/**
	 * @param array $product
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setProduct($product)
	{
		$this->product = $product;

		return $this;
	}

	/**
	 * @param shopSeofilterFilter $filter
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setFilter($filter)
	{
		$this->filter = $filter;

		return $this;
	}

	/**
	 * @param string $seofilter_url_suffix
	 * @return shopSeofilterRequestUrlParseResultBuilder
	 */
	public function setSeofilterUrlSuffix($seofilter_url_suffix)
	{
		$this->seofilter_url_suffix = $seofilter_url_suffix;

		return $this;
	}
}