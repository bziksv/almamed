<?php

class shopSeofilterBasicSettingsModel extends waModel
{
	const ENABLED = '1';
	const DISABLED = '0';

	protected $table = 'shop_seofilter_basic_settings';

	private static $settings_raw = null;

	/** @var shopSeofilterPluginSettings */
	private static $settings_object = null;

	public function __construct($type = null, $writable = false)
	{
		parent::__construct($type, $writable);

		if (self::$settings_raw === null)
		{
			self::$settings_raw = $this->loadSettings();
		}
	}

	/**
	 * @return shopSeofilterPluginSettings
	 */
	public static function getSettings()
	{
		if (self::$settings_raw === null)
		{
			new self();
		}

		if (self::$settings_object === null)
		{
			self::$settings_object = new shopSeofilterPluginSettings(self::$settings_raw);
		}

		return self::$settings_object;
	}

	public function loadSettings()
	{
		$rows = $this->getAll('name');
		/** @var stdClass $yandex_metric_codes */
		$yandex_metric_codes = json_decode(ifset($rows['yandex_metric_hit_codes']['value'], '{"*": ""}'), true);

		$settings = array(
			'is_enabled' => ifset($rows['is_enabled']['value'], self::ENABLED),
			'use_sitemap_hook' => ifset($rows['use_sitemap_hook']['value'], self::ENABLED),
			'url_type' => ifset($rows['url_type']['value'], shopSeofilterPluginUrlType::SHORT),
			'yandex_metric' => array(
				'is_enabled' => ifset($rows['yandex_metric_hit_is_enabled']['value'], self::DISABLED),
				'codes' => $yandex_metric_codes,
			),
			'sitemap_cache_default_storefront_hide_products' => ifset($rows['sitemap_cache_default_storefront_hide_products']['value']),
			'sitemap_cache_default_storefront_show_products' => ifset($rows['sitemap_cache_default_storefront_show_products']['value']),
			'sitemap_cache_check_after' => ifset($rows['sitemap_cache_check_after']['value']),
			'sitemap_cron_rebuild_queue_after' => ifset($rows['sitemap_cron_rebuild_queue_after']['value']),

			'category_additional_description_is_enabled' => ifset($rows['category_additional_description_is_enabled']['value'], self::DISABLED),
			'sitemap_lazy_generation' => ifset($rows['sitemap_lazy_generation']['value'], self::ENABLED),
			'append_page_number_is_enabled' => ifset($rows['append_page_number_is_enabled']['value'], self::DISABLED),

			'block_empty_feature_values' => ifset($rows['block_empty_feature_values']['value'], self::DISABLED),
			'empty_page_http_code' => (int)ifset($rows['empty_page_http_code']['value'], 404),
			'default_product_sort' => ifset($rows['default_product_sort']['value'], 'category'),
			'consider_category_filters' => ifset($rows['consider_category_filters']['value'], self::DISABLED),
			'cache_ttl_minutes' => ifset($rows['cache_ttl_minutes']['value'], 1440),
		);

		$hidden_config_file = wa('shop')->getConfigPath('shop/plugins/seofilter') . '/config.php';

		if (file_exists($hidden_config_file))
		{
			$hidden_config  = include($hidden_config_file);

			$fields = array(
				'root_url',
				'use_custom_products_collection',
				'excluded_get_params',
				'disable_on_save_handlers',
				'stop_propagation_in_frontend_script',
				'routing_steps_order',
				'keep_page_number_param',
				'cache_for_single_storefront',
				'use_sitemap_cache_for_checks',
			);
			foreach ($fields as $field)
			{
				if (array_key_exists($field, $hidden_config))
				{
					$settings[$field] = $hidden_config[$field];
				}
			}
		}

		return $settings;
	}

	public function saveSettings($basic_settings)
	{
		$settings = array(
			'is_enabled' => $basic_settings['is_enabled'],
			'use_sitemap_hook' => $basic_settings['use_sitemap_hook'],
			'url_type' => $basic_settings['url_type'],
			'yandex_metric_hit_is_enabled' => $basic_settings['yandex_metric']['is_enabled'],
			'yandex_metric_hit_codes' => json_encode($basic_settings['yandex_metric']['codes']),

			'category_additional_description_is_enabled' => $basic_settings['category_additional_description_is_enabled'],
			'append_page_number_is_enabled' => $basic_settings['append_page_number_is_enabled'],

			'block_empty_feature_values' => $basic_settings['block_empty_feature_values'],
			'sitemap_lazy_generation' => $basic_settings['sitemap_lazy_generation'],
			'empty_page_http_code' => $basic_settings['empty_page_http_code'],
			'default_product_sort' => $basic_settings['default_product_sort'],
			'consider_category_filters' => $basic_settings['consider_category_filters'],
			'cache_ttl_minutes' => $basic_settings['cache_ttl_minutes'],
		);

		foreach ($settings as $name => $value)
		{
			$this->insert(array(
				'name' => $name,
				'value' => $value,
			), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}

	public function isPluginEnabled()
	{
		return $this->getBoolOption('is_enabled');
	}

	public function useSitemapHook()
	{
		return $this->getBoolOption('use_sitemap_hook');
	}

	public function isCategoryAdditionalDescriptionEnabled()
	{
		return $this->getBoolOption('category_additional_description_is_enabled');
	}

	/**
	 * @param string $storefront
	 * @return false|string
	 */
	public function getYandexCounterCode($storefront)
	{
		if (self::$settings_raw['yandex_metric']['is_enabled'] == self::DISABLED)
		{
			return false;
		}

		if (array_key_exists($storefront, self::$settings_raw['yandex_metric']['codes']))
		{
			return self::$settings_raw['yandex_metric']['codes'][$storefront];
		}

		if (array_key_exists('*', self::$settings_raw['yandex_metric']['codes']))
		{
			self::$settings_raw['yandex_metric']['codes']['*'];
		}

		return '';
	}

	public function getUrlType()
	{
		return array_key_exists('url_type', self::$settings_raw)
			? self::$settings_raw['url_type']
			: null;
	}

	/**
	 * @param string $storefront
	 * @param bool $is_drop_out_of_stock
	 */
	public function setDefaultSitemapCacheStorefront($storefront, $is_drop_out_of_stock)
	{
		$key = $is_drop_out_of_stock
			? 'sitemap_cache_default_storefront_hide_products'
			: 'sitemap_cache_default_storefront_show_products';

		$this->insert(array(
			'name' => $key,
			'value' => $storefront,
		), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);

		self::$settings_raw[$key] = $storefront;

		self::$settings_object = null;
	}

	/**
	 * @param bool $in_stock_only
	 * @return string|null mixed
	 */
	public function getDefaultSitemapCacheStorefront($in_stock_only)
	{
		$key = $in_stock_only
			? 'sitemap_cache_default_storefront_hide_products'
			: 'sitemap_cache_default_storefront_show_products';

		return self::$settings_raw[$key];
	}

	public function getDefaultSitemapCacheStorefronts()
	{
		$result = array();

		if ($storefront = $this->getDefaultSitemapCacheStorefront(true))
		{
			$result[] = $storefront;
		}
		if ($storefront = $this->getDefaultSitemapCacheStorefront(false))
		{
			$result[] = $storefront;
		}

		return $result;
	}

	public function get($name)
	{
		return array_key_exists($name, self::$settings_raw)
			? self::$settings_raw[$name]
			: null;
	}

	public function set($name, $value)
	{
		if (!array_key_exists($name, self::$settings_raw))
		{
			return false;
		}

		$result = $this->insert(array(
			'name' => $name,
			'value' => $value,
		), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);

		if ($result)
		{
			self::$settings_raw[$name] = $value;

			self::$settings_object = new shopSeofilterPluginSettings(self::$settings_raw);
		}

		return $result;
	}

	private function getBoolOption($name)
	{
		return self::$settings_raw[$name] == self::ENABLED;
	}
}
