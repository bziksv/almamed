<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopWholesalePluginFrontendShippingController extends waJsonController {

    public function execute() {
        $shipping_id = waRequest::post('shipping_id', 0, waRequest::TYPE_INT);
        $check = shopWholesale::checkShipping($shipping_id);
        $this->response['check'] = $check;
    }

}
