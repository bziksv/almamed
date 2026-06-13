<?php

class shopUserlogProductSnapshot
{
    public static function capture($product_id)
    {
        $product_id = (int) $product_id;
        if (!$product_id) {
            return null;
        }

        $product_model = new shopProductModel();
        $product = $product_model->getById($product_id);
        if (!$product) {
            return null;
        }

        $skus_model = new shopProductSkusModel();
        $skus = $skus_model->getByField('product_id', $product_id, true);

        $images_model = new shopProductImagesModel();
        $images = $images_model->getByField('product_id', $product_id, true);

        $categories_model = new shopCategoryProductsModel();
        $categories = $categories_model->getByField('product_id', $product_id, true);

        $tags_model = new shopProductTagsModel();
        $tags = $tags_model->getTags($product_id);

        $params_model = new shopProductParamsModel();
        $params = $params_model->get($product_id);

        return array(
            'product'     => $product,
            'skus'        => $skus,
            'images'      => $images,
            'categories'  => $categories,
            'tags'        => $tags,
            'params'      => $params,
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    /**
     * Lightweight snapshot for action log (no images/files).
     */
    public static function captureForLog($product_id)
    {
        $product_id = (int) $product_id;
        if (!$product_id) {
            return null;
        }

        $product_model = new shopProductModel();
        $product = $product_model->getById($product_id);
        if (!$product) {
            return null;
        }

        $skus_model = new shopProductSkusModel();
        $skus = $skus_model->getByField('product_id', $product_id, true);
        $skus = self::trimSkus($skus);

        $categories_model = new shopCategoryProductsModel();
        $categories = $categories_model->getByField('product_id', $product_id, true);

        $tags_model = new shopProductTagsModel();
        $tags = $tags_model->getTags($product_id);

        return array(
            'product'     => self::trimProduct($product),
            'skus'        => $skus,
            'categories'  => $categories,
            'tags'        => $tags,
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    protected static function trimProduct(array $product)
    {
        $keys = array(
            'id', 'name', 'summary', 'meta_title', 'meta_keywords', 'meta_description',
            'description', 'contact_id', 'create_datetime', 'edit_datetime', 'status',
            'type_id', 'url', 'currency', 'price', 'compare_price', 'min_price', 'max_price',
            'count', 'sku_id', 'sku_count', 'sku_type', 'base_price_selectable',
        );
        return array_intersect_key($product, array_flip($keys));
    }

    protected static function trimSkus(array $skus)
    {
        $keys = array('id', 'sku', 'name', 'price', 'compare_price', 'purchase_price', 'available', 'status', 'sort', 'count');
        $result = array();
        foreach ($skus as $id => $sku) {
            $row = array_intersect_key($sku, array_flip($keys));
            $row['id'] = ifset($sku, 'id', $id);
            $result[$id] = $row;
        }
        return $result;
    }

    public static function captureLight($product_id)
    {
        return (new shopProductModel())->getById($product_id);
    }

    public static function flattenForDiff(array $snapshot)
    {
        $product = ifset($snapshot, 'product', array());
        $skus = ifset($snapshot, 'skus', array());
        $categories = ifset($snapshot, 'categories', array());
        $first_sku = $skus ? reset($skus) : array();

        $category_sort = array();
        foreach ($categories as $row) {
            $category_sort[(int) $row['category_id']] = (int) ifset($row, 'sort', 0);
        }

        $sku_flat = array();
        foreach ($skus as $id => $sku) {
            $sid = ifset($sku, 'id', $id);
            $label = ifset($sku, 'sku', '') ?: ifset($sku, 'name', 'SKU #'.$sid);
            $sku_flat[$sid] = array(
                'label'          => $label,
                'price'          => ifset($sku, 'price', null),
                'compare_price'  => ifset($sku, 'compare_price', null),
                'purchase_price' => ifset($sku, 'purchase_price', null),
                'sku'            => ifset($sku, 'sku', null),
                'available'      => ifset($sku, 'available', null),
            );
        }

        return array(
            'name'           => ifset($product, 'name', null),
            'price'          => ifset($product, 'price', null),
            'compare_price'  => ifset($product, 'compare_price', null),
            'status'         => ifset($product, 'status', null),
            'sku'            => ifset($first_sku, 'sku', null),
            'skus'           => $sku_flat,
            'category_sort'  => $category_sort,
        );
    }

    public static function copyFiles($product_id, $dest_path)
    {
        waFiles::create($dest_path);
        foreach (array(false, true) as $protected) {
            $src = shopProduct::getPath($product_id, null, $protected);
            if (file_exists($src)) {
                waFiles::copy($src, $dest_path.'/'.($protected ? 'protected' : 'public'));
            }
        }
    }

    public static function prepareForRestore(array $snapshot, $product_id)
    {
        $product_id = (int) $product_id;
        if (!empty($snapshot['product']) && is_array($snapshot['product'])) {
            $snapshot['product']['id'] = $product_id;
        }
        if (!empty($snapshot['skus']) && is_array($snapshot['skus'])) {
            $prepared = array();
            foreach ($snapshot['skus'] as $sku_id => $sku) {
                if (!is_array($sku)) {
                    continue;
                }
                $sku['product_id'] = $product_id;
                $sku['id'] = (int) ifset($sku, 'id', $sku_id);
                $prepared[$sku['id']] = $sku;
            }
            $snapshot['skus'] = $prepared;
        }
        return $snapshot;
    }

    public static function restore(array $snapshot, $files_path, $original_id)
    {
        wa('shop');
        $product_data = ifset($snapshot, 'product', array());
        if (!$product_data) {
            throw new waException('Пустой снимок товара');
        }

        $product_model = new shopProductModel();
        $existing = $product_model->getById($original_id);

        if ($existing) {
            $product = new shopProduct($original_id);
        } else {
            unset($product_data['id']);
            $product = new shopProduct();
            $product->save($product_data);
            if ($original_id && !$product->id) {
                throw new waException('Не удалось восстановить товар');
            }
        }

        if ($existing) {
            foreach ($product_data as $k => $v) {
                if ($k !== 'id') {
                    $product[$k] = $v;
                }
            }
        }

        if (!empty($snapshot['skus'])) {
            $product->skus = $snapshot['skus'];
        }

        if (!$product->save()) {
            throw new waException('Ошибка сохранения восстановленного товара');
        }

        $product_id = $product->getId();

        if (!empty($snapshot['categories'])) {
            $cp_model = new shopCategoryProductsModel();
            $cp_model->deleteByField('product_id', $product_id);
            foreach ($snapshot['categories'] as $row) {
                $cp_model->insert(array(
                    'category_id' => $row['category_id'],
                    'product_id'  => $product_id,
                    'sort'        => ifset($row, 'sort', 0),
                ), 2);
            }
        }

        if (!empty($snapshot['tags'])) {
            $tags_model = new shopProductTagsModel();
            $tags_model->setTags($product_id, $snapshot['tags']);
        }

        if ($files_path && file_exists($files_path)) {
            foreach (array('public' => false, 'protected' => true) as $folder => $protected) {
                $src = rtrim($files_path, '/').'/'.$folder;
                if (file_exists($src)) {
                    $dest = shopProduct::getPath($product_id, null, $protected);
                    waFiles::create(dirname($dest));
                    if (file_exists($dest)) {
                        waFiles::delete($dest);
                    }
                    waFiles::copy($src, $dest);
                }
            }
        }

        return $product_id;
    }
}
