<?php

class shopSeofilterProductfiltersCategoryFeatureRules
{
	private static $rules = array();

	private $storefront;

	public function __construct($storefront = null)
	{
		if ($storefront === null)
		{
			$storefront = shopSeofilterProductfiltersHelper::getStorefront();
		}

		if (!isset(self::$rules[$storefront]))
		{
			$model = new shopSeofilterProductfiltersCategoryFeatureRuleModel();
			self::$rules[$storefront] = $model->getSettings($storefront);
		}

		$this->storefront = $storefront;
	}

	public function getRules()
	{
		return self::$rules[$this->storefront];
	}

	public function getRulePart($storefront, $category_id, $feature_id)
	{
		return isset(self::$rules[$storefront][$category_id][$feature_id])
			? self::$rules[$storefront][$category_id][$feature_id]
			: null;
	}
}