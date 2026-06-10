<?php

class shopSeofilterDefaultTemplateSettingsModel extends waModel
{
	protected $table = 'shop_seofilter_default_template_settings';

	/** @var shopSeofilterDefaultTemplateSettings[] */
	private static $settings = array();

	public static function getSettings($storefront)
	{
		if (!array_key_exists($storefront, self::$settings))
		{
			$model = new self();

			$settings = $model->getByField('storefront', $storefront, true);

			$template_settings = array();
			foreach ($settings as $setting_row)
			{
				$template_settings[$setting_row['name']] = $setting_row['value'];
			}

			self::$settings[$storefront] = new shopSeofilterDefaultTemplateSettings($template_settings);
		}

		return self::$settings[$storefront];
	}
}