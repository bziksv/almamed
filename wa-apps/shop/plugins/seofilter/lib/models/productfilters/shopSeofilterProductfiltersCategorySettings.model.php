<?php

class shopSeofilterProductfiltersCategorySettingsModel extends waModel
{
	const DB_TRUE = '1';
	const DB_FALSE = '0';

	protected $table = 'shop_seofilter_productfilters_category_settings';

	public function getCategorySettings($storefront, $category_id)
	{
		$storefront_where_params = array(
			'storefront' => $storefront,
		);

		$boolean_settings = $this->getBooleanSettings();

		$settings = $this->select('name, value')
			->where('storefront = :storefront', $storefront_where_params)
			->where('category_id = :category_id', array('category_id' => $category_id))
			->fetchAll('name', true);

		foreach ($settings as $name => $value)
		{
			$settings[$name] = isset($boolean_settings[$name])
				? $value == self::DB_TRUE
				: $value;
		}

		return $settings;
	}

	public function getCategoriesSettings($storefront)
	{
		$storefront_where_params = array(
			'storefront' => $storefront,
		);

		$boolean_settings = $this->getBooleanSettings();

		$query = $this->select('category_id, name, value')
			->where('storefront = :storefront', $storefront_where_params)
			->query();

		$settings = array();
		foreach ($query as $row)
		{
			$category_id = $row['category_id'];
			$name = $row['name'];
			$value = $row['value'];

			if (!isset($settings[$category_id]))
			{
				$settings[$category_id] = array();
			}

			$settings[$category_id][$name] = isset($boolean_settings[$name])
				? $value == self::DB_TRUE
				: $value;
		}

		return $settings;
	}

	public function saveCategoriesSettings($storefront, $settings)
	{
		foreach ($settings as $category_id => $category_settings)
		{
			$this->saveCategorySettings($storefront, $category_id, $category_settings);
		}
	}

	public function saveCategorySettings($storefront, $category_id, $settings)
	{
		foreach ($settings as $setting => $value)
		{
			$this->saveCategorySettingValue($storefront, $category_id, $setting, $value);
		}
	}

	public function saveCategorySettingValue($storefront, $category_id, $setting, $value)
	{
		$boolean_settings = $this->getBooleanSettings();

		$data = array(
			'storefront' => $storefront,
			'category_id' => $category_id,
			'name' => $setting,
			'value' => $value,
		);

		if (isset($boolean_settings[$setting]))
		{
			$data['value'] = $value ? self::DB_TRUE : self::DB_FALSE;
		}

		$this->insert($data, self::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function getBooleanSettings()
	{
		return array(
			'is_enabled' => 'is_enabled',
		);
	}

	public function getDefaultSettings()
	{
		return array(
			'is_enabled' => true,
		);
	}
}