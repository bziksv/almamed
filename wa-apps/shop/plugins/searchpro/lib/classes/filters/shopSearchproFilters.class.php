<?php

class shopSearchproFilters
{
	protected $count;

	private $product_ids;
	private $products = array();

	private $features_helper;
	private $product_features_helper;

	/**
	 * @param array $product_ids
	 */
	public function __construct($product_ids)
	{
		$this->product_ids = $product_ids;

		$this->features_helper = new shopSearchproFeaturesHelper();
		$this->product_features_helper = new shopSearchproProductFeaturesHelper();
	}

	private function getFeaturesHelper()
	{
		return $this->features_helper;
	}

	private function getProductFeaturesHelper()
	{
		return $this->product_features_helper;
	}

	/**
	 * @return array $product_ids
	 */
	public function getProductIds()
	{
		return $this->product_ids;
	}

	/**
	 * @return int
	 */
	public function getProductCount()
	{
		if(!isset($this->count)) {
			$this->count = count($this->product_ids);
		}

		return $this->count;
	}

	/**
	 * @param bool $is_public_only
	 * @param bool $is_selectable_features
	 * @param array $disabled_features
	 * @return array $features
	 */
	public function getFeaturesValues($is_public_only = true, $is_selectable_features = true, array $disabled_features = array())
	{
		$features_values = $this->getProductFeaturesHelper()->getFeaturesValues($this->getProductIds(), $is_public_only, $is_selectable_features, $disabled_features);

		return $features_values;
	}
}
