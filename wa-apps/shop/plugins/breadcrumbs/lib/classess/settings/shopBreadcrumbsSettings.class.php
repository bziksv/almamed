<?php

/**
 * Class shopBreadcrumbsSettings
 * @property bool $show_subcategories
 * @property bool $show_subcategories_on_hover
 * @property bool $hide_current_item
 * @property bool $show_current_item_link
 *
 *
 * @property bool $add_seofilter_items
 * @property string $current_level_mode
 * @property string $categories_menu_mode
 * @property string $category_name_mode
 * @property bool $show_old_templates_in_settings
 *
 * @property string $product_seofilter_item_mode
 * @property array $seofilter_feature_ids
 */
class shopBreadcrumbsSettings
{
	const CURRENT_LEVEL_MODE_NONE = 'NONE';
	const CURRENT_LEVEL_MODE_SHOW = 'SHOW';
	const CURRENT_LEVEL_MODE_SHOW_AS_LINK = 'SHOW_AS_LINK';

	const CATEGORIES_MENU_MODE_NONE = 'NONE';
	const CATEGORIES_MENU_MODE_SHOW_ON_CLICK = 'SHOW_ON_CLICK';
	const CATEGORIES_MENU_MODE_SHOW_ON_HOVER = 'SHOW_ON_HOVER';

	const CATEGORY_NAME_MODE_NAME = 'NAME';
	const CATEGORY_NAME_MODE_SEO_NAME = 'SEO_NAME';
	const CATEGORY_NAME_MODE_SEO_H1 = 'SEO_H1';

	const PRODUCT_SEOFILTER_ITEM_MODE_FEATURE_NAME = 'FEATURE_NAME';
	const PRODUCT_SEOFILTER_ITEM_MODE_SEO_NAME = 'SEO_NAME';
	const PRODUCT_SEOFILTER_ITEM_MODE_CATEGORY_AND_FEATURE_NAME = 'CATEGORY_AND_FEATURE_NAME';
	const PRODUCT_SEOFILTER_ITEM_MODE_FILTER_H1 = 'FILTER_H1';

	private static $settings_raw = array();

	private $storefront;

	public function __construct($storefront = null)
	{
		$settings_model = new shopBreadcrumbsSettingsModel();
		$seofilter_features_model = new shopBreadcrumbsSeofilterFeatureModel();

		if ($storefront === null)
		{
			$storefront = shopBreadcrumbsPlugin::getStorefront();
		}

		$this->storefront = $storefront;
		if (!isset(self::$settings_raw[$storefront]))
		{
			self::$settings_raw[$storefront] = $settings_model->getSettings($storefront);

			self::$settings_raw[$storefront]['seofilter_feature_ids'] = array();

			if ($this->add_seofilter_items)
			{
				$feature_ids = $seofilter_features_model
					->select('sort,feature_id')
					->where('storefront = :storefront', array('storefront' => $storefront))
					->order('sort ASC')
					->fetchAll('sort', true);

				if (!count($feature_ids))
				{
					$feature_ids = $seofilter_features_model
						->select('sort,feature_id')
						->where('storefront = :storefront', array('storefront' => shopBreadcrumbsSettingsModel::STOREFRONT_GENERAL))
						->order('sort ASC')
						->fetchAll('sort', true);
				}

				self::$settings_raw[$storefront]['seofilter_feature_ids'] = array_values($feature_ids);
			}
		}
	}

	function __get($field)
	{
		$all_fields = $this->settingsFields();

		if ($field == 'show_subcategories')
		{
			return $this->categories_menu_mode != self::CATEGORIES_MENU_MODE_NONE;
		}
		elseif ($field == 'show_subcategories_on_hover')
		{
			return $this->categories_menu_mode == self::CATEGORIES_MENU_MODE_SHOW_ON_HOVER;
		}
		elseif ($field == 'hide_current_item')
		{
			return $this->current_level_mode == self::CURRENT_LEVEL_MODE_NONE;
		}
		elseif ($field == 'show_current_item_link')
		{
			return $this->current_level_mode == self::CURRENT_LEVEL_MODE_SHOW_AS_LINK;
		}
		elseif ($field == 'show_old_templates_in_settings')
		{
			$app_settings_model = new waAppSettingsModel();

			return $app_settings_model->get('shop.breadcrumbs', 'show_old_templates_in_settings', '0') == '1';
		}
		elseif (!array_key_exists($field, $all_fields))
		{
			throw new waException("shopBreadcrumbs plugin: unknown settings field [{$field}]");
		}

		return $this->getFieldValue($field);
	}

	private function settingsFields()
	{
		return array(
			'add_seofilter_items' => 'add_seofilter_items',
			'current_level_mode' => 'current_level_mode',
			'categories_menu_mode' => 'categories_menu_mode',
			'category_name_mode' => 'category_name_mode',
			'seofilter_feature_ids' => 'seofilter_feature_ids',
			'product_seofilter_item_mode' => 'product_seofilter_item_mode',
		);
	}

	private function booleanSettingsFields()
	{
		return array(
			'add_seofilter_items' => 'add_seofilter_items',
		);
	}

	private function settingsFieldsDefaultValues()
	{
		return array(
			'add_seofilter_items' => false,
			'current_level_mode' => self::CURRENT_LEVEL_MODE_SHOW,
			'categories_menu_mode' => self::CATEGORIES_MENU_MODE_SHOW_ON_CLICK,
			'category_name_mode' => self::CATEGORY_NAME_MODE_SEO_H1,
			'seofilter_feature_ids' => array(),
			'product_seofilter_item_mode' => self::PRODUCT_SEOFILTER_ITEM_MODE_FEATURE_NAME,
		);
	}

	private function getFieldValue($field)
	{
		if (isset(self::$settings_raw[$this->storefront][$field]))
		{
			$raw_value = self::$settings_raw[$this->storefront][$field];

			$bool_fields = $this->booleanSettingsFields();

			if (isset($bool_fields[$field]))
			{
				return $raw_value == shopBreadcrumbsSettingsModel::DB_TRUE;
			}

			return $raw_value;
		}
		else
		{
			$default_value = $this->settingsFieldsDefaultValues();

			return ifset($default_value[$field]);
		}
	}



	public function getRawSettings()
	{
		$settings_raw = self::$settings_raw[$this->storefront];

		$default_values = $this->settingsFieldsDefaultValues();
		$bool_fields = $this->booleanSettingsFields();
		foreach ($default_values as $field => $value)
		{
			if (!isset($settings_raw[$field]))
			{
				$settings_raw[$field] = isset($bool_fields[$field])
					? ($value ? shopBreadcrumbsSettingsModel::DB_TRUE : shopBreadcrumbsSettingsModel::DB_FALSE)
					: $value;
			}
		}

		foreach ($bool_fields as $bool_field)
		{
			$settings_raw[$bool_field] = $settings_raw[$bool_field] == shopBreadcrumbsSettingsModel::DB_TRUE;
		}

		return $settings_raw;
	}
}