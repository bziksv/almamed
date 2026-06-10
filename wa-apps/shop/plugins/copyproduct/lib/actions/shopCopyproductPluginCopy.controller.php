<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
class shopCopyproductPluginCopyController extends waJsonController
{
    /**
     * @var shopProductModel
     */
    protected $model;
    /**
     * @var shopProductSkusModel
     */
    protected $sku_model;
    protected $settings = array();
    public function execute()
    {
        $this->settings = wa()->getPlugin('copyproduct')->getSettings();
        $this->model = new shopProductModel();
        $this->sku_model = new shopProductSkusModel();
        $ids = waRequest::post('id');
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id) {
                    $this->copy($id);
                }
            }
        } else {
            $id = (int)$ids;
            $this->response['id'] = $this->copy($id);
        }
    }

    protected function copy($id)
    {
        $data = $this->model->getById($id);
        if (isset($data['id_1c'])) {
            unset($data['id_1c']);
        }
        $old = new shopProduct($data);

        unset($data['id']);
        unset($data['create_datetime']);
        unset($data['image_id']);

        if ($this->settings['hide_copy']) {
            $data['status'] = 0;
        }

        $data['rating'] = $data['rating_count'] = 0;
        $data['total_sales'] = 0;

        $data['skus'] = array();
        $i = -1;

        $sku_images = array();

        $feature_model = new shopFeatureModel();
        $feature_weight = $feature_model->getByCode('weight');

        // get sku features
        $pf_model = new shopProductFeaturesModel();
        $rows = $pf_model->where('product_id = '.(int)$id.' AND sku_id IS NOT NULL')->fetchAll();
        $skus_features = array();
        foreach ($rows as $row) {
            $skus_features[$row['sku_id']][$row['feature_id']] = $row['feature_value_id'];
        }

        foreach ($old['skus'] as $s) {
            if (isset($s['id_1c'])) {
                unset($s['id_1c']);
            }
            if (count($old['skus']) == 1 && $this->settings['clear_sku_name'] && $s['name']) {
                $s['name'] = '';
            }
            // clear sku
            if ($this->settings['clear_sku']) {
                $s['sku'] = '';
            }
            if ($data['sku_id'] > 0 && $s['id'] == $data['sku_id']) {
                $data['sku_id'] = $i;
            }
            // copy sku features
            if (isset($skus_features[$s['id']])) {
                $s['features'] = $pf_model->getValues($id, -$s['id']);
                if ($feature_weight && isset($s['features']['weight']) && isset($skus_features[$s['id']][$feature_weight['id']])) {
                    $s['features'][$feature_weight['id']] = $skus_features[$s['id']][$feature_weight['id']];
                }
            }
            unset($s['id']);
            if (!empty($this->settings['ignore_images'])) {
                $s['image_id'] = null;
            } else {
                if ($s['image_id']) {
                    $sku_images[] = $s['image_id'];
                }
            }
            // copy stocks and count
            if (!$s['stock'] && $s['count'] !== null && $s['count'] !== '') {
                $s['stock'][0] = $s['count'];
            }
            $data['skus'][$i--] = $s;
        }
        foreach (array('params', 'tags') as $k) {
            if ($old[$k]) {
                $data[$k] = $old[$k];
            }
        }

        if ($old['features']) {
            foreach ($old['features'] as $code => $v) {
                if ($v instanceof shopBooleanValue) {
                    $v = $v->value;
                }
                $data['features'][$code] = $v;
            }
        }

        if ($old['categories']) {
            $data['categories'] = array_keys($old['categories']);
        }

        // set unique url for new product
        $url = $this->model->query("SELECT url FROM shop_product
            WHERE url LIKE('".$this->model->escape($old['url'], 'like')."_%')
            ORDER BY url DESC LIMIT 1")->fetchField();
        if (!$url) {
            $data['url'] = $old['url'].'_1';
            $data['name'] .= ' (1)';
        } else {
            $postfix_pos = mb_strrpos($url, '_');
            $postfix = mb_substr($url, $postfix_pos + 1, mb_strlen($url));
            $cur_url = mb_substr($url, 0, $postfix_pos);
            $data['url'] = is_numeric($postfix) ? $cur_url.'_'.($postfix + 1) : $url.'_1';
            $data['name'] .= ' ('.(is_numeric($postfix) ? ($postfix + 1) : 1).')';
        }

        $new = new shopProduct();
        if ($old['sku_type']) {
            $new->features_selectable = array();
        }
        $new->save($data);
        $product_id = $new->getId();

        if ($old['sku_type']) {
            // copy features selectable
            $fs_model = new shopProductFeaturesSelectableModel();
            $rows = $fs_model->getByField('product_id', $id, true);
            foreach ($rows as &$row) {
                $row['product_id'] = $product_id;
            }
            unset($row);
            $fs_model->multipleInsert($rows);
        }

        // copy pages
        $pages_model = new shopProductPagesModel();
        $pages = $pages_model->getByField('product_id', $id, true);
        if ($pages) {
            foreach ($pages as $page) {
                $page['product_id'] = $product_id;
                unset($page['id']);
                $pages_model->add($page);
            }
        }

        if (empty($this->settings['ignore_images']) && $old['images']) {
            $images_model = new shopProductImagesModel();
            // copy images
            foreach ($old['images'] as $img) {
                $new_img = $img;
                unset($new_img['id']);
                $new_img['product_id'] = $product_id;
                $new_img['id'] = $images_model->add($new_img, $old['image_id'] == $img['id']);

                waFiles::copy(shopImage::getPath($img), shopImage::getPath($new_img));
                if (file_exists(shopImage::getOriginalPath($img))) {
                    waFiles::copy(shopImage::getOriginalPath($img), shopImage::getOriginalPath($new_img));
                }

                if (in_array($img['id'], $sku_images)) {
                    $this->sku_model->updateByField(array('product_id' => $product_id, 'image_id' => $img['id']),
                        array('image_id' => $new_img['id']));
                }
            }
        }

        // related products
        if ($this->settings['copy_related']) {
            $related_model = new shopProductRelatedModel();
            $rows = $related_model->getByField('product_id', $id, true);
            if ($rows) {
                foreach ($rows as &$row) {
                    $row['product_id'] = $product_id;
                }
                unset($row);
                $related_model->multipleInsert($rows);
            }
        }

        // services
        $product_services_model = new shopProductServicesModel();
        $rows = $product_services_model->getByField('product_id', $id, true);
        if ($rows) {
            $sku_map = array();
            $i = 0;
            $new_skus = array_keys($new['skus']);
            foreach ($old['skus'] as $sku_id => $s) {
                $sku_map[$sku_id] = $new_skus[$i++];
            }
            foreach ($rows as $row) {
                $row['product_id'] = $product_id;
                if ($row['sku_id']) {
                    if (isset($sku_map[$row['sku_id']])) {
                        $row['sku_id'] = $sku_map[$row['sku_id']];
                    } else {
                        continue;
                    }
                }
                unset($row['id']);
                $product_services_model->insert($row);
            }
        }
        return $product_id;
    }
}
