<?php

class shopBreadcrumbsSettingsModel extends waModel
{
	const STOREFRONT_GENERAL = '*';

	const DB_TRUE = '1';
	const DB_FALSE = '0';

	protected $table = 'shop_breadcrumbs_settings';

	public function getSettings($storefront)
	{
		$setting = array();

		$settings_query = $this
			->select('*')
			->where('storefront IN (:storefronts)', array('storefronts' => array(self::STOREFRONT_GENERAL, $storefront)))
			->order("(storefront <> '" . self::STOREFRONT_GENERAL . "') DESC")
			->query();

		foreach ($settings_query as $row)
		{
			if (!isset($setting[$row['name']]))
			{
				$setting[$row['name']] = $row['value'];
			}
		}

		return $setting;
	}

	public function set($storefront, $option, $value)
	{
		$data = array(
			'storefront' => $storefront,
			'name' => $option,
			'value' => $value,
		);

		$this->insert($data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function availableSettings()
	{
		return array(
			'add_seofilter_items' => 'add_seofilter_items',
			'current_level_mode' => 'current_level_mode',
			'categories_menu_mode' => 'categories_menu_mode',
			'category_name_mode' => 'category_name_mode',
			'product_seofilter_item_mode' => 'product_seofilter_item_mode',
		);
	}
}