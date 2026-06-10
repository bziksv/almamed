<?php

class shopQuickeditorPluginBackendStorefrontController extends waJsonController
{
    public function execute()
    {
        try {
            $domain = strtolower(waRequest::post('domain', '', waRequest::TYPE_STRING));
            $quickeditor = wa('shop')->getPlugin('quickeditor');
            $storefronts = $quickeditor->getStorefronts();
            $storefronts[] = 'main';
            if (!in_array($domain, $storefronts)) {
                throw new waException(_wp('Storefront not found'));
            }
            $settings = $quickeditor->getSettings('main');
            $settings['active_storefront'] = $domain;
            if ($settings['active_storefront'] == 'main') {
                $settings['enable_multisettings'] = false;
            } else {
                $settings['enable_multisettings'] = true;
            }
            $quickeditor->saveSettings(array('main' => $settings));
            $this->response['saved'] = true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            $this->response['saved'] = false;
        }
    }
}
