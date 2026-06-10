<?php

class shopFrontendCartCountController extends waJsonController
{
    public function execute()
    {
        wa()->getResponse()->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        wa()->getResponse()->addHeader('Pragma', 'no-cache');

        $cart = new shopCart();
        $this->response['count'] = $cart->count();
        $this->response['total'] = shop_currency_html($cart->total(), wa('shop')->getConfig()->getCurrency(false));
    }
}
