<?php

/**
 * @property-read bool $is_product_page
 * @property-read bool $is_category_page
 * @property-read bool $is_seofilter_page
 * @property-read array|null $category
 * @property-read array|null $product
 * @property-read shopSeofilterFilter|null $filter
 * @property-read string|null$seofilter_url_suffix
 */
class shopSeofilterRequestUrlParseResult
{
	private $is_product_page;
	private $is_category_page;
	private $is_seofilter_page;

	private $category;
	private $product;
	private $filter;
	private $seofilter_url_suffix;

	public function __construct(
		$is_product_page,
		$is_category_page,
		$is_seofilter_page,
		$category,
		$product,
		$filter,
		$seofilter_url_suffix
	)
	{
		$this->is_product_page = $is_product_page;
		$this->is_category_page = $is_category_page;
		$this->is_seofilter_page = $is_seofilter_page;
		$this->category = $category;
		$this->product = $product;
		$this->filter = $filter;
		$this->seofilter_url_suffix = $seofilter_url_suffix;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}