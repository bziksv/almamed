<?php

class shopCartsPluginFrontendHeartbeatController extends waController
{
    public function execute()
    {
        /**
         * @var shopCartsPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin('carts');
        $plugin->saveStorefront(null);

        $response = wa()->getResponse();
        $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->addHeader('Pragma', 'no-cache');
        $response->addHeader('Expires', '0');
        $response->addHeader('Content-type', 'application/json');
        $response->sendHeaders();

        echo '"ok"';
    }

}