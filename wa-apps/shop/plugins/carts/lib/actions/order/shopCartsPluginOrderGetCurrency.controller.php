<?php


class shopCartsPluginOrderGetCurrencyController extends waJsonController
{

    public function execute()
    {
        if($code = waRequest::get('code')) {
            $sm = new shopCartsPluginStorefrontModel();
            $this->response = $sm->getLastByCode($code);
        } else {
            $this->setError(_wp('Unknown cart'));
        }
    }
}