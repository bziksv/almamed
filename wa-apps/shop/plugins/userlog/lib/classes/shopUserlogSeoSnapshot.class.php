<?php

class shopUserlogSeoSnapshot
{
    public static function isAvailable()
    {
        return wa()->appExists('shop') && wa('shop')->getPlugin('seo');
    }

    public static function captureCategoryState($category_id)
    {
        if (!self::isAvailable()) {
            return null;
        }
        try {
            $dialog = shopSeoContext::getInstance()->getWaBackendCategoryDialog();
            return $dialog->getState((int) $category_id);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function captureProductState($product_id)
    {
        if (!self::isAvailable()) {
            return null;
        }
        try {
            $edit = shopSeoContext::getInstance()->getWaProductEdit();
            return $edit->getState((int) $product_id);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function capturePluginSettingsState()
    {
        if (!self::isAvailable()) {
            return null;
        }
        try {
            $page = shopSeoContext::getInstance()->getWaSettingsPage();
            return $page->getState(array(), array());
        } catch (Exception $e) {
            return null;
        }
    }

    public static function flattenEntityState($state)
    {
        if (!is_array($state)) {
            return array();
        }
        $flat = array();
        if (!empty($state['general_settings']) && is_array($state['general_settings'])) {
            foreach ($state['general_settings'] as $key => $value) {
                $flat['general.'.$key] = self::scalar($value);
            }
        }
        if (!empty($state['general_fields_values']) && is_array($state['general_fields_values'])) {
            foreach ($state['general_fields_values'] as $key => $value) {
                $flat['field.'.$key] = self::scalar($value);
            }
        }
        if (!empty($state['settings'])) {
            $flat['storefront_settings'] = waUtils::jsonEncode($state['settings'], JSON_UNESCAPED_UNICODE);
        }
        if (!empty($state['fields_values'])) {
            $flat['storefront_fields'] = waUtils::jsonEncode($state['fields_values'], JSON_UNESCAPED_UNICODE);
        }
        ksort($flat);
        return $flat;
    }

    public static function flattenPluginSettingsState($state)
    {
        if (!is_array($state)) {
            return array();
        }
        $flat = array();
        if (!empty($state['plugin_settings']) && is_array($state['plugin_settings'])) {
            foreach ($state['plugin_settings'] as $key => $value) {
                $flat['plugin.'.$key] = self::scalar($value);
            }
        }
        if (!empty($state['general_settings']) && is_array($state['general_settings'])) {
            foreach ($state['general_settings'] as $key => $value) {
                $flat['general.'.$key] = self::scalar($value);
            }
        }
        ksort($flat);
        return $flat;
    }

    public static function flattenFromRequestJson($state_json)
    {
        $state = json_decode((string) $state_json, true);
        return self::flattenEntityState(is_array($state) ? $state : array());
    }

    protected static function scalar($value)
    {
        if (is_array($value)) {
            return waUtils::jsonEncode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($value === null) {
            return '';
        }
        return (string) $value;
    }
}
