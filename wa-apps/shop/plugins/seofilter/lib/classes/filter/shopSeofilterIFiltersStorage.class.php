<?php

interface shopSeofilterIFiltersStorage
{
	/**
	 * @param int $filter_id
	 * @return shopSeofilterFilter|null
	 */
	public function getById($filter_id);

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @param string $filter_url
	 * @return shopSeofilterFilter
	 */
	public function getByUrl($storefront, $category_id, $filter_url);

	/**
	 * @param string $storefront
	 * @param int $category_id
	 * @param array $filter_params
	 * @param string $currency
	 * @return shopSeofilterFilter|null
	 */
	public function getByFilterParams($storefront, $category_id, $filter_params, $currency);
}