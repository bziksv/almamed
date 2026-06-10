<?php

/**
 * Class shopSeofilterFilter_1477563297
 *
 * @property $url
 * @property $seo_name
 * @property $featureValue
 * @property $personalRules
 */
class shopSeofilterFilter_1477563297 extends shopSeofilterAR_1477563297
{
	/** @var waModel */
	public static $model = null;

	public function save()
	{
		if (self::$model === null)
		{
			self::$model = new waModel();
		}

		$filter_id = $this->saveFilter();

		if ($filter_id)
		{
			$this->saveFeatureValue($filter_id);
			$this->saveRules($filter_id);
		}

		return true;
	}

	private function saveFilter()
	{
		$sql = '
INSERT INTO `shop_seofilter_filter`
(`url`, `seo_name`, `feature_value_hash`, `update_datetime`)
VALUES (:url, :seo_name, :feature_value_hash, :update_datetime)
';

		/** @var shopSeofilterFilterFeatureValue_1477563297 $featureValue */
		$featureValue = $this->attributes['featureValue'];

		$params = array(
			'url' => strtolower($this->attributes['url']),
			'seo_name' => $this->attributes['seo_name'],
			'feature_value_hash' => $featureValue->hash(),
			'update_datetime' => date('Y-m-d H:i:s'),
		);

		return self::$model->query($sql, $params)->lastInsertId();
	}

	private function saveFeatureValue($filter_id)
	{
		/** @var shopSeofilterFilterFeatureValue_1477563297 $featureValue */
		$featureValue = $this->attributes['featureValue'];

		$sql = '
INSERT INTO `shop_seofilter_filter_feature_value`
(`filter_id`, `feature_id`, `value_id`, `sort`)
VALUES (:filter_id, :feature_id, :value_id, :sort)
';

		$params = array(
			'filter_id' => $filter_id,
			'feature_id' => $featureValue->attributes['feature_id'],
			'value_id' => $featureValue->attributes['value_id'],
			'sort' => $featureValue->attributes['sort'],
		);

		self::$model->query($sql, $params);
	}

	private function saveRules($filter_id)
	{
		/** @var shopSeofilterFilterPersonalRule_1477563297[] $rules */
		$rules = $this->attributes['personalRules'];

		$insert_rule_sql = '
INSERT INTO `shop_seofilter_filter_personal_rule`
(`filter_id`, `seo_h1`, `seo_description`, `meta_title`, `meta_description`, `meta_keywords`, `storefronts_use_mode`, `categories_use_mode`)
VALUES (:filter_id, :seo_h1, :seo_description, :meta_title, :meta_description, :meta_keywords, :storefronts_use_mode, :categories_use_mode)
';
		$insert_rule_category_sql = '
INSERT INTO `shop_seofilter_filter_personal_rule_category`
(`rule_id`, `category_id`)
VALUES (:rule_id, :category_id)
';

		$insert_rule_storefront_sql = '
INSERT INTO `shop_seofilter_filter_personal_rule_storefront`
(`rule_id`, `storefront`)
VALUES (:rule_id, :storefront)
';

		foreach ($rules as $rule)
		{
			$rule_params = array(
				'filter_id' => $filter_id,
				'seo_h1' => $rule->attributes['seo_h1'],
				'seo_description' => $rule->attributes['seo_description'],
				'meta_title' => $rule->attributes['meta_title'],
				'meta_description' => $rule->attributes['meta_description'],
				'meta_keywords' => $rule->attributes['meta_keywords'],
				'storefronts_use_mode' => ifset($rule->attributes['storefronts_use_mode'], shopSeofilterFilterPersonalRule_1477563297::USE_MODE_ALL),
				'categories_use_mode' => ifset($rule->attributes['categories_use_mode'], shopSeofilterFilterPersonalRule_1477563297::USE_MODE_ALL),
			);

			$rule_id = self::$model->query($insert_rule_sql, $rule_params)->lastInsertId();




			if ($rule_id && $rule->attributes['categories_use_mode'] == shopSeofilterFilterPersonalRule_1477563297::USE_MODE_LISTED)
			{
				foreach ($rule->attributes['rule_categories'] as $category)
				{
					$category_params = array(
						'rule_id' => $rule_id,
						'category_id' => $category,
					);

					self::$model->query($insert_rule_category_sql, $category_params);
				}
			}

			if ($rule_id && $rule->attributes['storefronts_use_mode'] == shopSeofilterFilterPersonalRule_1477563297::USE_MODE_LISTED)
			{
				foreach ($rule->attributes['rule_storefronts'] as $storefront)
				{
					$category_params = array(
						'rule_id' => $rule_id,
						'storefront' => $storefront,
					);

					self::$model->query($insert_rule_storefront_sql, $category_params);
				}
			}
		}
	}
}