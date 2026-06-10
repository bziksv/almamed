<?php

class shopSeofilterProductfiltersCategoryFeatureRuleModel extends waModel
{
	const DISPLAY_LINK_YES = 'Y';
	const DISPLAY_LINK_NO = 'N';

	protected $table = 'shop_seofilter_productfilters_category_feature_rule';

	public function getSettings($storefront)
	{
		$setting = array();

		$settings_query = $this
			->select('*')
			->where('storefront IN (:storefronts)', array('storefronts' => array(shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL, $storefront)))
			->order("(storefront <> '" . shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL . "') DESC")
			->query();

		foreach ($settings_query as $row)
		{
			$category_id = $row['category_id'];
			$feature_id = $row['feature_id'];
			$link_category_id = $row['link_category_id'];
			$display_link = $row['display_link'];

			if (!isset($setting[$category_id]))
			{
				$setting[$category_id] = array();
			}

			if (!isset($setting[$category_id][$feature_id]))
			{
				$setting[$category_id][$feature_id] = array(
					'link_category_id' => $link_category_id,
					'display_link' => $display_link == self::DISPLAY_LINK_YES,
				);
			}
		}

		return $setting;
	}

	public function saveRule($storefront, $category_id, $rule)
	{
		foreach ($rule as $feature_id => $option)
		{
			$row = array(
				'storefront' => $storefront,
				'category_id' => $category_id,
				'feature_id' => $feature_id,
				'link_category_id' => $option['link_category_id'],
				'display_link' => $option['display_link'] ? self::DISPLAY_LINK_YES : self::DISPLAY_LINK_NO,
			);

			if ($option['link_category_id'] == $category_id || !$option['link_category_id'])
			{
				$row['link_category_id'] = 0;
			}

			$this->insert($row, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}
}