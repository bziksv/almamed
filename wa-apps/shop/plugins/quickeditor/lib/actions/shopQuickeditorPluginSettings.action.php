<?php

class shopQuickeditorPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $quickeditor = wa('shop')->getPlugin('quickeditor');
        $this->view->assign('storefronts', array_merge($quickeditor->getStorefronts()));

        $db_settings = $quickeditor->getSettings('main');
        if (!is_array($db_settings)) {
            $db_settings = array();
        }
        $main_settings = array_merge($quickeditor->getDefaultMainSettings(), $quickeditor->getDefaultSettings(), $db_settings);
        foreach ($main_settings as $setting_name => $setting_value) {
            $this->view->assign($setting_name, $setting_value);
        }

        if ($main_settings['enable_multisettings'] && $main_settings['active_storefront'] != 'main') {
            $db_settings = $quickeditor->getSettings($main_settings['active_storefront']);
            if (!is_array($db_settings)) {
                $db_settings = array();
            }
            $storefront_settings = array_merge($quickeditor->getDefaultSettings(), $db_settings);
        } else {
            $storefront_settings = $main_settings;
        }

        foreach ($storefront_settings as $setting_name => $setting_value) {
            $this->view->assign($setting_name, $setting_value);
        }
    }
}
