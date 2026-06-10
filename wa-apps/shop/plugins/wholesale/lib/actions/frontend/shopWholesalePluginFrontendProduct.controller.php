<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopWholesalePluginFrontendProductController extends waJsonController {

    public function execute() {
        try {
            $quantity = waRequest::post('quantity', 1);
            $old_quantity = waRequest::post('old_quantity');
            $product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
            if (!$product_id) {
                throw new waException('Минимальный заказ: product_id не определен');
            }
            $sku_id = waRequest::post('sku_id', 0, waRequest::TYPE_INT);
            if (!$sku_id) {
                $features = waRequest::post('features', array());
                $product_features_model = new shopProductFeaturesModel();
                $sku_id = $product_features_model->getSkuByFeatures($product_id, $features);
            }

            $check = shopWholesale::checkProduct($product_id, $sku_id, $quantity, $old_quantity);
            $this->response['check'] = $check;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
