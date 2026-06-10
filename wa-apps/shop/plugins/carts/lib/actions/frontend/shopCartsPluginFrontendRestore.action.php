<?php

class shopCartsPluginFrontendRestoreAction extends waViewAction {

    public function execute()
    {
        if($hash = waRequest::param('hash')) {
            // header for IE
            $response = $this->getResponse();
            $response->addHeader('P3P', 'CP="NOI ADM DEV COM NAV OUR STP"');
            $expire = time() + 30 * 86400;
            $response->setCookie('shop_cart', $hash, $expire, null, '', false, true);
            $this->getStorage()->set('shop/cart', array());

            $storefront_model = new shopCartsPluginStorefrontModel();
            $storefront_data = $storefront_model->getLastByCode($hash);
            $storefront_model->updateById($storefront_data['id'], array(
                'restore' => 1,
            ));
        }

        if(waRequest::param('checkout_version') == 2) {
            $url = wa()->getRouteUrl('shop/frontend/order');
        } else {
            $url = wa()->getRouteUrl('shop/frontend/cart');
        }

        if($get = waRequest::get()) {
            $url .= '?'.http_build_query($get);
        }
        $this->redirect($url);
    }
}