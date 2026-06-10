<?php

class shopCartsPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        /**
         * @var shopCartsPlugin $plugin
         */
        try {
            $plugin = wa('shop')->getPlugin('carts');
            $plugin->saveSettings(waRequest::post('shop_carts', array()));
        } catch(waException $e) {
            $this->setError($e->getMessage());
        }
    }
}
