<?php

class shopSeofilterProductfiltersSettingsModel extends waModel
{
	protected $table = 'shop_seofilter_productfilters_settings';

	const DB_TRUE = '1';
	const DB_FALSE = '0';

	public function getRawSettings($storefront)
	{
		$setting = array();

		$settings_query = $this
			->select('*')
			->where('storefront IN (:storefronts)', array('storefronts' => array(shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL, $storefront)))
			->order("(storefront <> '" . shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL . "') DESC")
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

	public function set($storefront, $setting, $value)
	{
		$data = array(
			'storefront' => $storefront,
			'name' => $setting,
			'value' => $value,
		);

		$this->insert($data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function availableSettings()
	{
		return array(
			'is_enabled',
			'link_type',
			'custom_link_text',
			'open_link_in_new_tab',
		);
	}

	public function saveSettings($storefront, $settings)
	{
		$settings_obj = new shopSeofilterProductfiltersSettings();
		$boolean_settings = $settings_obj->booleanFields();

		foreach ($settings as $setting => $value)
		{
			if (isset($boolean_settings[$setting]))
			{
				$value = $value ? self::DB_TRUE : self::DB_FALSE;
			}

			$this->set($storefront, $setting, $value);
		}
	}
}
