<?php

/**
 * Class shopSeofilterDefaultTemplateSettings
 * @property bool $pagination_is_enabled
 * @property bool $is_enabled
 * @property bool $use_sitemap_hook
 * @property string $url_type
 * @property bool $yandex_metric_enabled
 * @property array $yandex_metric_codes
 * @property string $sitemap_cache_default_storefront_hide_products
 * @property string $sitemap_cache_default_storefront_show_products
 * @property int $sitemap_cache_check_after
 * @property bool $category_additional_description_is_enabled
 * @property bool $append_page_number_is_enabled
 * @property bool $use_custom_products_collection
 * @property string $root_url
 * @property array $excluded_get_params
 * @property bool $keep_page_number_param
 * @property bool $block_empty_feature_values
 * @property bool $disable_on_save_handlers
 * @property bool $sitemap_lazy_generation
 * @property int $sitemap_cron_rebuild_queue_after
 * @property bool $stop_propagation_in_frontend_script
 * @property int $empty_page_http_code
 * @property string $default_product_sort
 * @property bool $consider_category_filters
 * @property int $cache_ttl_minutes
 * @property array $routing_steps_order
 * @property bool $cache_for_single_storefront
 * @property bool $use_sitemap_cache_for_checks
 */
class shopSeofilterPluginSettings extends shopSeofilterSettings
{
	/**
	 * @param string $storefront
	 * @return false|string
	 */
	public function getYandexCounterCode($storefront)
	{
		if (!$this->yandex_metric_enabled)
		{
			return false;
		}

		$codes = $this->yandex_metric_codes;

		return ifset($codes[$storefront], ifset($codes['*'], ''));
	}

	public function getDefaultSitemapCacheStorefront($in_stock_only)
	{
		return $in_stock_only
			? $this->sitemap_cache_default_storefront_hide_products
			: $this->sitemap_cache_default_storefront_show_products;
	}

	function __get($name)
	{
		if ($name == 'yandex_metric_enabled')
		{
			$settings = $this->getRawSettings();

			return $this->prepareSettingValue($name, $settings['yandex_metric']['is_enabled']);
		}
		elseif ($name == 'yandex_metric_codes')
		{
			$settings = $this->getRawSettings();

			return $this->prepareSettingValue($name, $settings['yandex_metric']['codes']);
		}
		elseif ($name == 'empty_page_http_code')
		{
			$code = parent::__get('empty_page_http_code');

			return wa_is_int($code) ? $code : 404;
		}
		elseif ($name == 'is_enabled')
		{
			return parent::__get('is_enabled') && shopSeofilterHelper::isSeofilterInstalled();
		}
		else
		{
			return parent::__get($name);
		}
	}

	public function getDefaultProductSortSort()
	{
		if (!$this->default_product_sort)
		{
			return '';
		}

		$tmp = explode(' ', $this->default_product_sort);
		return count($tmp) > 0 ? $tmp[0] : '';
	}

	public function getDefaultProductSortOrder()
	{
		if (!$this->default_product_sort)
		{
			return '';
		}

		$tmp = explode(' ', $this->default_product_sort);
		return count($tmp) == 2 ? $tmp[1] : '';
	}

	protected function defaultSettings()
	{
		return array(
			'is_enabled' => self::DB_TRUE,
			'use_sitemap_hook' => self::DB_TRUE,
			'url_type' => shopSeofilterPluginUrlType::SHORT,
			'yandex_metric_enabled' => self::DB_FALSE,
			'yandex_metric_codes' => array('*' => ''),
			'sitemap_cache_default_storefront_hide_products' => null,
			'sitemap_cache_default_storefront_show_products' => null,
			'sitemap_cache_check_after' => null,
			'category_additional_description_is_enabled' => self::DB_FALSE,
			'append_page_number_is_enabled' => self::DB_FALSE,
			'use_custom_products_collection' => false,
			'root_url' => null,
			'excluded_get_params' => array(),
			'block_empty_feature_values' => self::DB_FALSE,
			'disable_on_save_handlers' => self::DB_FALSE,
			'sitemap_lazy_generation' => self::DB_TRUE,
			'sitemap_cron_rebuild_queue_after' => null,
			'stop_propagation_in_frontend_script' => true,
			'keep_page_number_param' => false,
			'empty_page_http_code' => 404,
			'default_product_sort' => '',
			'consider_category_filters' => self::DB_FALSE,
			'cache_ttl_minutes' => 1440,
			'cache_for_single_storefront' => false,
			'use_sitemap_cache_for_checks' => false,
		);
	}

	protected function booleanSettingsFields()
	{
		return array(
			'is_enabled' => 1,
			'use_sitemap_hook' => 1,
			'yandex_metric_enabled' => 1,
			'category_additional_description_is_enabled' => 1,
			'append_page_number_is_enabled' => 1,
			'use_custom_products_collection' => 1,
			'block_empty_feature_values' => 1,
			'disable_on_save_handlers' => 1,
			'sitemap_lazy_generation' => 1,
			'stop_propagation_in_frontend_script' => 1,
			'keep_page_number_param' => 1,
			'consider_category_filters' => 1,
			'cache_for_single_storefront' => 1,
			'use_sitemap_cache_for_checks' => 1,
		);
	}
}
