<?php

// todo все получения категорий: заменить на этот класс
class shopSeofilterWaCategoryStorage
{
	private static $cached_categories = array();

	/** @var shopCategoryModel */
	private static $category_model = null;

	/**
	 * @param int $category_id
	 * @return shopSeofilterWaCategory|null
	 */
	public function getCategory($category_id)
	{
		if (!array_key_exists($category_id, self::$cached_categories))
		{
			self::$cached_categories[$category_id] = $this->fetchCategory($category_id);
		}

		return self::$cached_categories[$category_id];
	}

	/**
	 * @param int $category_id
	 * @return array
	 */
	public function getFilterFeatureIds($category_id)
	{
		$category = $this->getCategory($category_id);
		if (!$category)
		{
			return array();
		}

		$filter_imploded = shopSeofilterHelper::isSmartfiltersPluginEnabled() && is_string($category->smartfilters) && trim($category->smartfilters) !== ''
			? $category->smartfilters
			: $category->filter;

		return is_string($filter_imploded) && trim($filter_imploded) !== ''
			? explode(',', trim($filter_imploded))
			: array();
	}

	private function fetchCategory($category_id)
	{
		$category_assoc = $this->getCategoryModel()->getByField('id', $category_id);

		return is_array($category_assoc)
			? new shopSeofilterWaCategory($category_assoc)
			: null;
	}

	/**
	 * @return shopCategoryModel
	 */
	private function getCategoryModel()
	{
		if (self::$category_model === null)
		{
			self::$category_model = new shopCategoryModel();
		}

		return self::$category_model;
	}
}
