<?php

class shopProductfeaturesPlugin extends shopPlugin
{
    public static function limit()
    {
        $settings_model = new waAppSettingsModel();
        return $settings_model->get(array('shop', 'productfeatures'), 'limit');
    }

    public function backendProducts()
    {
        if (wa()->getUser()->isAdmin('shop') || wa()->getUser()->getRights('shop', 'type.%')) {
            $view = wa()->getView();
            $view->assign('plugin_url', $this->getPluginStaticUrl());
            $view->assign('plugin_version', $this->getVersion());

            return array(
                'toolbar_section' => $view->fetch($this->path . '/templates/Toolbar.html')
            );
        }
    }

    public static function features($products)
    {
        $settings_model = new waAppSettingsModel();
        if ($settings_model->get(array('shop', 'productfeatures'), 'limit') === '0') {
            return array();
        }

        if ($products instanceof shopProduct) {
            // get settings
            $summary_features = $settings_model->get(array('shop', 'productfeatures'), 'product_summary');
            if ($summary_features) {
                $summary_features = json_decode($summary_features, true);
            }
            $p = $products;
            if (!$summary_features && empty($summary_features[$p['type_id']])) {
                return array();
            }
            // get feature codes
            $feature_model = new shopFeatureModel();
            $features = $feature_model->getById($summary_features[$p['type_id']]);
            $codes = array();
            foreach ($features as $f) {
                $codes[] = $f['code'];
            }
            // filter features
            $result = array();
            foreach ($p['features'] as $code => $v) {
                if (in_array($code, $codes)) {
                    $result[$code] = $v;
                }
            }
            return $result;
        } else {
            $summary_features = $settings_model->get(array('shop', 'productfeatures'), 'summary');
            if ($summary_features) {
                $summary_features = json_decode($summary_features, true);
            }
            if (!$summary_features) {
                return array();
            }

            $product_ids = array();
            $feature_ids = array();
            $type_ids = array();
            foreach ($products as $p) {
                if (!$p['summary'] && !empty($summary_features[$p['type_id']])) {
                    $product_ids[] = $p['id'];
                    $feature_ids = array_merge($feature_ids, $summary_features[$p['type_id']]);
                    $type_ids[] = $p['type_id'];
                }
            }
            if (!$type_ids) {
                return array();
            }
            $feature_ids = array_unique($feature_ids);
            $type_ids = array_unique($type_ids);

            $feature_model = new shopFeatureModel();
            $features = $feature_model->getById($feature_ids);

            // get product features
            $product_features_model = new shopProductFeaturesModel();
            $rows = $product_features_model->getByField(array(
                'product_id' => $product_ids, 'feature_id' => $feature_ids, 'sku_id' => null), true);

            $type_values = $values = array();
            $product_features = array();
            foreach ($rows as $row) {
                $f_id = $row['feature_id'];
                $type_values[$features[$f_id]['type']][] = $row['feature_value_id'];
                if ($features[$f_id]['multiple']) {
                    $product_features[$row['product_id']][$f_id][] = $row['feature_value_id'];
                } else {
                    $product_features[$row['product_id']][$f_id] = $row['feature_value_id'];
                }
            }

            foreach ($type_values as $type => $value_ids) {
                $model = shopFeatureModel::getValuesModel($type);
                $values += $model->getValues('id', $value_ids);
            }

            // get type features for correct sort
            $type_features_model = new shopTypeFeaturesModel();
            $sql = "SELECT type_id, feature_id FROM ".$type_features_model->getTableName()."
                    WHERE type_id IN (i:type_id)
                    ORDER BY sort";
            $rows = $type_features_model->query($sql, array('type_id' => $type_ids))->fetchAll();
            $type_features = array();
            foreach ($rows as $row) {
                if (!empty($summary_features[$row['type_id']]) && in_array($row['feature_id'], $summary_features[$row['type_id']])) {
                    $type_features[$row['type_id']][] = $row['feature_id'];
                }
            }
            $result = array();
            foreach ($products as $p) {
                if (!empty($type_features[$p['type_id']])) {
                    foreach ($type_features[$p['type_id']] as $feature_id) {
                        if (!empty($product_features[$p['id']][$feature_id])) {
                            $value_ids = $product_features[$p['id']][$feature_id];
                            $f = $features[$feature_id];
                            if (is_array($value_ids)) {
                                $result['values'][$p['id']][$f['code']] = array();
                                foreach ($value_ids as $v_id) {
                                    if (isset($values[$feature_id][$v_id])) {
                                        $result['values'][$p['id']][$f['code']][$v_id] = $values[$feature_id][$v_id];
                                    }
                                }
                            } elseif (isset($values[$feature_id][$value_ids])) {
                                $result['values'][$p['id']][$f['code']] = $values[$feature_id][$value_ids];
                            }
                        }
                    }
                }
            }
            foreach ($features as $f) {
                $result['features'][$f['code']] = $f;
            }
            return $result;
        }
    }
}