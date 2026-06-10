<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopWholesalePluginFrontendCartController extends waJsonController {

    public function execute() {
        $check = shopWholesale::checkOrder();
        $this->response['check'] = $check;
    }

}
