<?php

class shopUserlogSeofilterSnapshot
{
    public static function isAvailable()
    {
        return wa()->appExists('shop') && wa('shop')->getPlugin('seofilter');
    }

    public static function capturePluginSettings()
    {
        if (!self::isAvailable()) {
            return array();
        }
        $basic = shopSeofilterBasicSettingsModel::getSettings();
        return array(
            'basic'             => waUtils::jsonEncode($basic->getRawSettings(), JSON_UNESCAPED_UNICODE),
            'template_rules'    => waUtils::jsonEncode(
                (new shopSeofilterDefaultTemplateModel())->select('*')->fetchAll(),
                JSON_UNESCAPED_UNICODE
            ),
            'filter_fields'     => waUtils::jsonEncode(shopSeofilterFilterFieldModel::getAllFields(), JSON_UNESCAPED_UNICODE),
            'storefront_fields' => waUtils::jsonEncode(shopSeofilterStorefrontFieldsModel::getAllFields(), JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureProductfiltersSettings()
    {
        if (!self::isAvailable()) {
            return array();
        }
        return array(
            'settings'      => waUtils::jsonEncode(
                (new shopSeofilterProductfiltersSettingsModel())->select('*')->fetchAll(),
                JSON_UNESCAPED_UNICODE
            ),
            'categories'    => waUtils::jsonEncode(
                (new shopSeofilterProductfiltersCategorySettingsModel())->select('*')->fetchAll(),
                JSON_UNESCAPED_UNICODE
            ),
            'feature_rules' => waUtils::jsonEncode(
                (new shopSeofilterProductfiltersCategoryFeatureRuleModel())->select('*')->fetchAll(),
                JSON_UNESCAPED_UNICODE
            ),
        );
    }

    public static function captureFilter($filter_id)
    {
        if (!self::isAvailable() || !$filter_id) {
            return array();
        }
        $filter = (new shopSeofilterFilter())->getById((int) $filter_id);
        if (!$filter) {
            return array();
        }
        return array(
            'seo_name'               => (string) $filter->seo_name,
            'url'                    => (string) $filter->url,
            'seo_h1'                 => userlogHelper::plainTextForDisplay((string) $filter->seo_h1, 120),
            'seo_description'        => userlogHelper::plainTextForDisplay((string) $filter->seo_description, 120),
            'meta_title'             => userlogHelper::plainTextForDisplay((string) $filter->meta_title, 120),
            'meta_description'       => userlogHelper::plainTextForDisplay((string) $filter->meta_description, 120),
            'meta_keywords'          => userlogHelper::plainTextForDisplay((string) $filter->meta_keywords, 120),
            'is_enabled'             => (string) $filter->is_enabled,
            'categories'             => waUtils::jsonEncode($filter->filter_categories, JSON_UNESCAPED_UNICODE),
            'feature_values_count'   => (string) $filter->feature_values_count,
        );
    }
}
