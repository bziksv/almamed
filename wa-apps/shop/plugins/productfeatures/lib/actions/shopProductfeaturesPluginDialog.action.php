<?php

class shopProductfeaturesPluginDialogAction extends waViewAction
{
    public function execute()
    {
        $product_model = new shopProductModel();
        $hash = waRequest::post('hash', '');
        $values = array();
        if (!$hash) {
            $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
            if (!$product_ids) {
                return;
            }
            $sql = "SELECT DISTINCT type_id FROM shop_product
                    WHERE id IN (i:ids) AND type_id IS NOT NULL AND type_id != 0";
            $type_ids = $product_model->query($sql, array('ids' => $product_ids))->fetchAll(null, true);
            if (count($product_ids) == 1) {
                $p = new shopProduct($product_ids[0]);
                $values = $p->features;
            }
        } else {
            $c = new shopProductfeaturesProductsCollection($hash);
            $type_ids = $c->getTypeIds();
        }

        if ($type_ids) {
            $model = new shopFeatureModel();
            $sql = "SELECT f.* FROM shop_feature f
                    JOIN shop_type_features tf ON (tf.feature_id = f.id)
                    WHERE tf.type_id IN (i:ids) AND f.parent_id IS NULL AND f.type != 'divider'
                    ORDER BY tf.type_id, tf.sort";
            $features = $model->query($sql, array('ids' => $type_ids))->fetchAll('code');

            foreach ($features as & $feature) {
                $feature['feature_id'] = intval($feature['id']);
            }
            unset($feature);
            $features = $model->getValues($features);
        } else {
            $features = array();
        }
        $this->view->assign('features', $features);
        $this->view->assign('values', $values);
    }
}