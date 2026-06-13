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

        $params_model = new shopProductParamsModel();
        $params = $params_model->get($product_id);

        $sets = (new shopSetProductsModel())->getByProduct($product_id);

        $images_model = new shopProductImagesModel();
        $images = $images_model->getByField('product_id', $product_id, true);

        return array(
            'product'      => self::trimProduct($product),
            'skus'         => $skus,
            'categories'   => $categories,
            'tags'         => $tags,
            'params'       => $params ?: array(),
            'sets'         => $sets ?: array(),
            'images'       => self::trimImages($images),
            'features'     => self::captureFeaturesForDiff($product_id, $product),
            'feature_rows' => self::captureFeatureRows($product_id),
            'captured_at'  => date('Y-m-d H:i:s'),
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

    protected static function trimImages(array $images)
    {
        $result = array();
        foreach ($images as $id => $image) {
            if (!is_array($image)) {
                continue;
            }
            $result[(int) ifset($image, 'id', $id)] = array(
                'id'       => (int) ifset($image, 'id', $id),
                'filename' => ifset($image, 'filename', ''),
                'sort'     => (int) ifset($image, 'sort', 0),
            );
        }
        ksort($result);
        return $result;
    }

    protected static function captureFeatureRows($product_id)
    {
        $model = new shopProductFeaturesModel();
        return $model->select('*')
            ->where('product_id = i:id', array('id' => (int) $product_id))
            ->fetchAll();
    }

    protected static function captureFeaturesForDiff($product_id, array $product)
    {
        $pf_model = new shopProductFeaturesModel();
        $values = $pf_model->getValues(
            (int) $product_id,
            null,
            ifset($product, 'type_id', null),
            (int) ifset($product, 'sku_type', 0)
        );

        $flat = array();
        foreach ($values as $code => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $string = self::featureValueToString($value);
            if ($string !== '') {
                $flat[$code] = $string;
            }
        }
        ksort($flat);

        return $flat;
    }

    protected static function featureValueToString($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            $parts = array();
            foreach ($value as $item) {
                $part = self::featureValueToString($item);
                if ($part !== '') {
                    $parts[] = $part;
                }
            }
            return implode(', ', $parts);
        }
        if ($value instanceof shopCompositeValue) {
            return trim((string) $value);
        }
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return trim((string) $value);
            }
            if (method_exists($value, 'getValue')) {
                return trim((string) $value->getValue());
            }
        }

        return trim((string) $value);
    }

    protected static function normalizeText($text)
    {
        return userlogHelper::plainTextForDisplay((string) $text);
    }

    protected static function flattenCategories(array $categories)
    {
        if (!$categories) {
            return '';
        }
        $ids = array();
        foreach ($categories as $row) {
            $ids[] = (int) $row['category_id'];
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return '';
        }
        $names = array();
        foreach ((new shopCategoryModel())->getById($ids) as $category) {
            $names[] = ifset($category, 'name', '').' (#'.(int) $category['id'].')';
        }
        sort($names, SORT_STRING);

        return implode(', ', $names);
    }

    protected static function flattenTags(array $tags)
    {
        if (!$tags) {
            return '';
        }
        $tags = array_map('strval', $tags);
        sort($tags, SORT_STRING);

        return implode(', ', $tags);
    }

    protected static function flattenParams(array $params)
    {
        if (!$params) {
            return '';
        }
        ksort($params);
        $parts = array();
        foreach ($params as $name => $value) {
            $parts[] = $name.': '.$value;
        }

        return implode('; ', $parts);
    }

    protected static function flattenSets(array $sets)
    {
        if (!$sets) {
            return '';
        }
        $names = array();
        foreach ($sets as $set) {
            $names[] = ifset($set, 'name', ifset($set, 'id', '')).' ('.ifset($set, 'id', '').')';
        }
        sort($names, SORT_STRING);
        return implode(', ', $names);
    }

    protected static function flattenImages(array $images)
    {
        if (!$images) {
            return '';
        }
        $parts = array();
        foreach ($images as $image) {
            if (!is_array($image)) {
                continue;
            }
            $parts[] = '#'.ifset($image, 'id', 0).':'.ifset($image, 'filename', '');
        }
        return implode(', ', $parts);
    }

    protected static function formatSkuType($sku_type)
    {
        if ((int) $sku_type === shopProductModel::SKU_TYPE_SELECTABLE) {
            return 'Выбор характеристик';
        }
        return 'Плоский';
    }

    protected static function resolveTypeName($type_id)
    {
        $type_id = (int) $type_id;
        if (!$type_id) {
            return '';
        }
        $type = (new shopTypeModel())->getById($type_id);

        return ifset($type, 'name', (string) $type_id);
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
                'name'           => ifset($sku, 'name', null),
                'price'          => ifset($sku, 'price', null),
                'compare_price'  => ifset($sku, 'compare_price', null),
                'purchase_price' => ifset($sku, 'purchase_price', null),
                'sku'            => ifset($sku, 'sku', null),
                'available'      => ifset($sku, 'available', null),
                'status'         => ifset($sku, 'status', null),
                'count'          => ifset($sku, 'count', null),
            );
        }

        return array(
            'name'              => ifset($product, 'name', null),
            'summary'           => self::normalizeText(ifset($product, 'summary', '')),
            'description'       => self::normalizeText(ifset($product, 'description', '')),
            'meta_title'        => ifset($product, 'meta_title', null),
            'meta_keywords'     => ifset($product, 'meta_keywords', null),
            'meta_description'  => ifset($product, 'meta_description', null),
            'url'               => ifset($product, 'url', null),
            'type'              => self::resolveTypeName(ifset($product, 'type_id', 0)),
            'price'             => ifset($product, 'price', null),
            'compare_price'     => ifset($product, 'compare_price', null),
            'min_price'         => ifset($product, 'min_price', null),
            'max_price'         => ifset($product, 'max_price', null),
            'currency'          => ifset($product, 'currency', null),
            'status'            => ifset($product, 'status', null),
            'count'             => ifset($product, 'count', null),
            'sku_type'          => self::formatSkuType(ifset($product, 'sku_type', 0)),
            'sku'               => ifset($first_sku, 'sku', null),
            'skus'              => $sku_flat,
            'categories'        => self::flattenCategories($categories),
            'tags'              => self::flattenTags(ifset($snapshot, 'tags', array())),
            'sets'              => self::flattenSets(ifset($snapshot, 'sets', array())),
            'params'            => self::flattenParams(ifset($snapshot, 'params', array())),
            'images'            => self::flattenImages(ifset($snapshot, 'images', array())),
            'features'          => ifset($snapshot, 'features', array()),
            'category_sort'     => $category_sort,
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

        if (array_key_exists('params', $snapshot)) {
            $params_model = new shopProductParamsModel();
            $params_model->set($product_id, (array) $snapshot['params']);
        }

        if (array_key_exists('feature_rows', $snapshot)) {
            $pf_model = new shopProductFeaturesModel();
            $pf_model->deleteByField('product_id', $product_id);
            foreach ((array) $snapshot['feature_rows'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $pf_model->insert(array(
                    'product_id'       => $product_id,
                    'feature_id'       => (int) $row['feature_id'],
                    'feature_value_id' => (int) $row['feature_value_id'],
                    'sku_id'           => ifset($row, 'sku_id', null),
                ), 2);
            }
        }

        if (!empty($snapshot['sets'])) {
            $set_ids = array();
            foreach ((array) $snapshot['sets'] as $set) {
                if (is_array($set)) {
                    $set_ids[] = ifset($set, 'id', '');
                } else {
                    $set_ids[] = $set;
                }
            }
            $set_ids = array_filter($set_ids);
            if ($set_ids) {
                $set_product = new shopProduct($product_id);
                $set_product->sets = $set_ids;
                $set_product->save();
            }
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
