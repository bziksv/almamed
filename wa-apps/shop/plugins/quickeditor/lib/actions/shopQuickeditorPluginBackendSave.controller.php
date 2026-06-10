<?php

class shopQuickeditorPluginBackendSaveController extends waJsonController
{
    public function execute()
    {
        try {
            $quickeditor = wa('shop')->getPlugin('quickeditor');
            $main_settings = array();
            $main_settings['enable_multisettings'] = (bool) waRequest::post('enable_multisettings', 0, waRequest::TYPE_INT);
            $main_settings['active_storefront'] = waRequest::post('active_storefront', '', waRequest::TYPE_STRING_TRIM);
            $settings = array(
                'enable_plugin' => (bool) waRequest::post('enable_plugin', 0, waRequest::TYPE_INT),
                'frontend_head_hook' => (bool) waRequest::post('frontend_head_hook', 0, waRequest::TYPE_INT),
                'always_edit_page' => (bool) waRequest::post('always_edit_page', 0, waRequest::TYPE_INT),
                'tab_window' => (bool) waRequest::post('tab_window', 0, waRequest::TYPE_INT),
                'show_description' => (bool) waRequest::post('show_description', 0, waRequest::TYPE_INT),
                'location_on_product' => waRequest::post('location_on_product', 'right', waRequest::TYPE_STRING),
                'category_link_location' => waRequest::post('category_link_location', 'right', waRequest::TYPE_STRING),
                'page_link_location' => waRequest::post('page_link_location', 'right', waRequest::TYPE_STRING),
                'quick_access_location' => waRequest::post('quick_access_location', 'right', waRequest::TYPE_STRING),
                'save_and_close' => (bool) waRequest::post('save_and_close', 0, waRequest::TYPE_INT),
                'show_buttons_title' => (bool) waRequest::post('show_buttons_title', 0, waRequest::TYPE_INT),
                'open_setting_tab' => waRequest::post('open_setting_tab', '/', waRequest::TYPE_STRING),
                'edit_category_settings' => (bool) waRequest::post('edit_category_settings', 0, waRequest::TYPE_INT),                
            );

            if (!preg_match('/^(block|block_aux|menu|cart|left|right|hidden)$/', $settings['location_on_product'])
                || !preg_match('/^(left|right|hidden)$/', $settings['category_link_location'])
                || !preg_match('/^(left|right|hidden)$/', $settings['page_link_location'])
                || !preg_match('/^(descriptions\/|images\/|features\/|services\/|related\/)$/', $settings['open_setting_tab'])
            ) {
                throw new waException(_wp('Please enter a valid plugin parameters'));
            }

            //Save
            if ($main_settings['enable_multisettings']) {
                $quickeditor->saveSettings(array($main_settings['active_storefront'] => $settings));
            } else {
                $main_settings['active_storefront'] = 'main';
                $quickeditor->saveSettings(array('main' => array_merge($settings, $main_settings)));
            }

            //Reset setings
            if (waRequest::post('reset_settings', 0, waRequest::TYPE_INT) == 1) {
                $settings = $quickeditor->getDefaultSettings();
                $quickeditor->saveSettings(array($main_settings['active_storefront'] => $settings));
            }

            $this->response['message'] = _wp('Saved');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
