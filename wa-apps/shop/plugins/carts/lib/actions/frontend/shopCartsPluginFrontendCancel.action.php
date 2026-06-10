<?php

class shopCartsPluginFrontendCancelAction extends waViewAction {


    public function execute()
    {
        if($hash = waRequest::param('hash')) {
            // header for IE
            $response = $this->getResponse();
            $response->addHeader('P3P', 'CP="NOI ADM DEV COM NAV OUR STP"');

            $code = md5(uniqid(time(), true));
            $expire = time() + 30 * 86400;
            $response->setCookie('shop_cart', $code, $expire, null, '', false, true);
            $this->getStorage()->set('shop/cart', array());

            $model = new shopCartItemsModel();
            $model->deleteByField('code', $hash);


            $storefront_model = new shopCartsPluginStorefrontModel();
            $storefront_data = $storefront_model->getLastByCode($hash);
            $storefront_model->updateById($storefront_data['id'], array(
                'cancel' => 1,
            ));
        }

        $url = wa()->getRouteUrl('shop/frontend');

        if($get = waRequest::get()) {
            $url .= '?'.http_build_query($get);
        }
        $this->redirect($url);
    }
}