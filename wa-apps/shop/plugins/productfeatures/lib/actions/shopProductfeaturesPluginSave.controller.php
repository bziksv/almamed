<?php

class shopProductfeaturesPluginSaveController extends waJsonController
{
    protected $values;
    protected $features;
    protected $type_features;
    protected $features_id_code;
    protected $checkbox_add = false;
    protected $rights;

    protected function deleteFeatures()
    {
        $delete = waRequest::post('features_delete');
        if (!$delete) {
            return;
        }
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getByCode(array_keys($delete));
        if (!$features) {
            return;
        }
        $feature_ids = array();
        foreach ($features as $f) {
            $feature_ids[] = $f['id'];
        }

        $model = new shopProductFeaturesModel();

        $hash = waRequest::post('hash', '');
        if (!$hash) {
            $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
            if (!$product_ids) {
                return;
            }
            if ($this->rights !== true) {
                $product_model = new shopProductModel();
                $products = $product_model->select('id,type_id')->where('id IN (i:ids)', array('ids' => $product_ids))->fetchAll('id');
                $product_ids = array();
                foreach ($products as $p) {
                    if (!empty($this->rights[$p['type_id']])) {
                        $product_ids[] = $p['id'];
                    }
                }
                if (!$product_ids) {
                    return;
                }
            }
            $model->deleteByField(array(
                'product_id' => $product_ids,
                'feature_id' => $feature_ids
            ));
        } else {
            $collection = new shopProductfeaturesProductsCollection($hash);
            $offset = 0;
            $count = 100;
            $total_count = $collection->count();
            while ($offset < $total_count) {
                $products = $collection->getProducts('id,type_id', $offset, $count);
                if ($products) {
                    // is admin
                    if ($this->rights === true) {
                        $product_ids = array_keys($products);
                    } else {
                        $product_ids = array();
                        foreach ($products as $p) {
                            if (!empty($this->rights[$p['type_id']])) {
                                $product_ids[] = $p['id'];
                            }
                        }
                    }
                    if ($product_ids) {
                        $model->deleteByField(array(
                            'product_id' => $product_ids,
                            'feature_id' => $feature_ids
                        ));
                    }
                }
                $offset += count($products);
            }
        }
    }
    
    public function execute()
    {
        if (wa()->getUser()->isAdmin('shop')) {
            $this->rights = true;
        } else {
            $this->rights = wa()->getUser()->getRights('shop', 'type.%');
        }

        $this->deleteFeatures();

        $this->values = waRequest::post('features');

        foreach ($this->values as $code => $v) {
            if ($v === '' || (is_array($v) && isset($v['value']) && $v['value'] === '')) {
                unset($this->values[$code]);
            }
        }

        if (!$this->values) {
            return;
        }

        $feature_model = new shopFeatureModel();
        $this->features = $feature_model->getByCode(array_keys($this->values));

        foreach ($this->features as $code => $f) {
            if (!$f['selectable']) {
                $v = $this->values[$code];
                if ($v !== '' || (is_array($v) && isset($v['value']) && $v['value'] !== '')) {
                    if (substr($f['type'], 0, 6) == 'range.') {
                        if (isset($v['value']['begin']) && $v['value']['begin'] === '' &&
                            isset($v['value']['end']) && $v['value']['end'] === '') {
                            unset($this->values[$code]);
                        }
                    } elseif (is_array($v) && preg_match('/^(.+\.)[12]$/', $code, $matches) && isset($this->values[$matches[1].'0'])) {
                        $v = array_merge($this->values[$matches[1].'0'], $v);
                        $this->values[$code] = $v;
                    }
                } else {
                    unset($this->values[$code]);
                }
            }
        }

        foreach ($this->features as $code => $f) {
            if (!$f['selectable'] && isset($this->values[$code])) {
                $v = $this->values[$code];
                $this->values[$code] = $feature_model->getValueId($f, $v, true);
            }
        }
        foreach ($this->values as $code => $v) {
            if ($v === '' || $v === false) {
                unset($this->values[$code]);
            }
        }

        if (!$this->features) {
            return;
        }

        /**
         * @var shopProductfeaturesPlugin $plugin
         */
        $plugin = wa()->getPlugin('productfeatures');
        $this->checkbox_add = $plugin->getSettings('checkbox_add');

        $this->features_id_code = array();
        $children_codes = array();
        foreach ($this->features as $code => &$f) {
            $this->features_id_code[$f['id']] = $code;
            if ($f['parent_id']) {
                $children_codes[$f['parent_id']][$f['id']] = $code;
            }
        }
        unset($f);

        $type_features_model = new shopTypeFeaturesModel();
        $rows = $type_features_model->getByField(
            'feature_id',
            array_merge(
                array_keys($this->features_id_code),
                array_keys($children_codes)
            ),
            true
        );
        $this->type_features = array();
        foreach ($rows as $row) {
            if ($row['type_id']) {
                if (isset($this->features_id_code[$row['feature_id']])) {
                    $this->type_features[$row['type_id']][$row['feature_id']] = $this->features_id_code[$row['feature_id']];
                }
                if (isset($children_codes[$row['feature_id']])) {
                    foreach ($children_codes[$row['feature_id']] as $c_id => $c_code) {
                        $this->type_features[$row['type_id']][$c_id] = $c_code;
                    }
                }
            }
        }

        $product_model = new shopProductModel();
        $hash = waRequest::post('hash', '');
        if (!$hash) {
            $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
            if (!$product_ids) {
                return;
            }
            if (count($product_ids) == 1) {
                $this->checkbox_add = 0;
            }
            $products = $product_model->select('id,type_id')->where('id IN (i:ids)', array('ids' => $product_ids))->fetchAll('id');
            $this->setFeatures($products);
        } else {
            $collection = new shopProductfeaturesProductsCollection($hash);
            $offset = 0;
            $count = 100;
            $total_count = $collection->count();
            while ($offset < $total_count) {
                $products = $collection->getProducts('id,type_id', $offset, $count);
                $this->setFeatures($products);
                $offset += count($products);
            }
        }
    }

    protected function setFeatures($products)
    {
        $model = new shopProductFeaturesModel();
        $rows = $model->getByField(array(
            'product_id' => array_keys($products),
            'sku_id' => null,
            'feature_id' => array_keys($this->features_id_code)
        ), true);
        // get old values
        $old = array();
        foreach ($rows as $row) {
            $old[$row['product_id']][$row['feature_id']][$row['feature_value_id']] = $row['id'];
        }

        $delete = array();
        $insert = array();
        foreach ($products as $p) {
            if ($p['type_id']) {
                if ($this->rights !== true && empty($this->rights[$p['type_id']])) {
                    continue;
                }
                foreach ($this->type_features[$p['type_id']] as $code) {
                    if (isset($this->values[$code]) && isset($this->features[$code])) {
                        $feature_id = $this->features[$code]['id'];
                        foreach ((array)$this->values[$code] as $v) {
                            if (isset($old[$p['id']][$feature_id][$v])) {
                                unset($old[$p['id']][$feature_id][$v]);
                            } else {
                                $insert[] = array(
                                    'product_id' => $p['id'],
                                    'sku_id' => null,
                                    'feature_id' => $feature_id,
                                    'feature_value_id' => $v
                                );
                            }
                        }
                        if (empty($this->features[$code]['multiple']) || !$this->checkbox_add) {
                            if (!empty($old[$p['id']][$feature_id])) {
                                foreach ($old[$p['id']][$feature_id] as $pfv_id) {
                                    $delete[] = $pfv_id;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($delete) {
            $model->deleteById($delete);
        }
        if ($insert) {
            $model->multipleInsert($insert);
        }
    }
}
