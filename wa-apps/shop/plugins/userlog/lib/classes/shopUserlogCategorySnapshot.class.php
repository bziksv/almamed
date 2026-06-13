<?php

class shopUserlogCategorySnapshot
{
    public static function capture($category_id)
    {
        $category_id = (int) $category_id;
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        if (!$category) {
            return null;
        }

        $cp_model = new shopCategoryProductsModel();
        $products = $cp_model->getByField('category_id', $category_id, true);

        $params_model = new shopCategoryParamsModel();
        $params = $params_model->get($category_id);

        return array(
            'category'   => $category,
            'products'   => $products,
            'params'     => $params,
            'captured_at'=> date('Y-m-d H:i:s'),
        );
    }

    public static function captureForLog($category_id)
    {
        $category_id = (int) $category_id;
        if (!$category_id) {
            return null;
        }

        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        if (!$category) {
            return null;
        }

        $params_model = new shopCategoryParamsModel();
        $params = $params_model->get($category_id);

        $routes_model = new shopCategoryRoutesModel();
        $routes = $routes_model->getRoutes($category_id);

        $og_model = new shopCategoryOgModel();
        $og = $og_model->get($category_id);

        return array(
            'category'    => self::trimCategory($category),
            'params'      => $params ?: array(),
            'routes'      => is_array($routes) ? array_values($routes) : array(),
            'og'          => is_array($og) ? $og : array(),
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    protected static function trimCategory(array $category)
    {
        $keys = array(
            'id', 'parent_id', 'name', 'url', 'description', 'meta_title', 'meta_keywords',
            'meta_description', 'type', 'status', 'sort', 'filter', 'conditions',
            'sort_products', 'include_sub_categories', 'create_datetime', 'edit_datetime',
        );
        return array_intersect_key($category, array_flip($keys));
    }

    protected static function normalizeText($text)
    {
        if (wa()->appExists('userlog')) {
            if (!waSystem::isLoaded('userlog')) {
                wa('userlog');
            }
            if (class_exists('userlogHelper')) {
                return userlogHelper::plainTextForDisplay((string) $text);
            }
        }
        return trim(strip_tags((string) $text));
    }

    protected static function formatType($type)
    {
        return (int) $type === shopCategoryModel::TYPE_DYNAMIC ? 'Динамическая' : 'Статическая';
    }

    protected static function formatStatus($status)
    {
        return (int) $status ? 'Видима' : 'Скрыта';
    }

    protected static function resolveParentName($parent_id)
    {
        $parent_id = (int) $parent_id;
        if (!$parent_id) {
            return 'Корень';
        }
        $parent = (new shopCategoryModel())->getById($parent_id);
        return ifset($parent, 'name', '#'.$parent_id);
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

    protected static function flattenRoutes(array $routes)
    {
        if (!$routes) {
            return '';
        }
        $routes = array_map('strval', $routes);
        sort($routes, SORT_STRING);
        return implode(', ', $routes);
    }

    protected static function flattenOg(array $og)
    {
        if (!$og) {
            return '';
        }
        ksort($og);
        $parts = array();
        foreach ($og as $property => $content) {
            $parts[] = $property.': '.$content;
        }
        return implode('; ', $parts);
    }

    public static function flattenForDiff(array $snapshot)
    {
        $category = ifset($snapshot, 'category', array());

        return array(
            'name'                   => ifset($category, 'name', ''),
            'url'                    => ifset($category, 'url', ''),
            'description'            => self::normalizeText(ifset($category, 'description', '')),
            'meta_title'             => ifset($category, 'meta_title', ''),
            'meta_keywords'          => ifset($category, 'meta_keywords', ''),
            'meta_description'       => ifset($category, 'meta_description', ''),
            'type'                   => self::formatType(ifset($category, 'type', 0)),
            'status'                 => self::formatStatus(ifset($category, 'status', 1)),
            'parent'                 => self::resolveParentName(ifset($category, 'parent_id', 0)),
            'sort_products'          => ifset($category, 'sort_products', ''),
            'include_sub_categories' => !empty($category['include_sub_categories']) ? 'да' : 'нет',
            'filter'                 => ifset($category, 'filter', ''),
            'conditions'             => ifset($category, 'conditions', ''),
            'params'                 => self::flattenParams(ifset($snapshot, 'params', array())),
            'routes'                 => self::flattenRoutes(ifset($snapshot, 'routes', array())),
            'og'                     => self::flattenOg(ifset($snapshot, 'og', array())),
        );
    }

    public static function restore(array $snapshot, $original_id)
    {
        wa('shop');
        $category_data = ifset($snapshot, 'category', array());
        if (!$category_data) {
            throw new waException('Пустой снимок категории');
        }

        $category_model = new shopCategoryModel();
        if ($category_model->getById($original_id)) {
            throw new waException('Категория с ID '.$original_id.' уже существует');
        }

        $category_data['id'] = $original_id;
        $category_model->insert($category_data, 1);

        if (!empty($snapshot['params'])) {
            (new shopCategoryParamsModel())->set($original_id, $snapshot['params']);
        }

        if (!empty($snapshot['products'])) {
            $cp_model = new shopCategoryProductsModel();
            foreach ($snapshot['products'] as $row) {
                $cp_model->insert(array(
                    'category_id' => $original_id,
                    'product_id'  => $row['product_id'],
                    'sort'        => ifset($row, 'sort', 0),
                ), 2);
            }
        }

        shopCategories::clear($original_id);

        $category_model->repair();

        return $original_id;
    }

    public static function restoreForUpdate(array $snapshot, $category_id)
    {
        wa('shop');
        $category_id = (int) $category_id;
        $category_data = ifset($snapshot, 'category', array());
        if (!$category_id || !$category_data) {
            throw new waException('Пустой снимок категории');
        }

        $model = new shopCategoryModel();
        if (!$model->getById($category_id)) {
            throw new waException('Категория не найдена');
        }

        unset($category_data['id']);
        $model->update($category_id, $category_data);

        if (array_key_exists('params', $snapshot)) {
            (new shopCategoryParamsModel())->set($category_id, (array) $snapshot['params']);
        }
        if (array_key_exists('routes', $snapshot)) {
            (new shopCategoryRoutesModel())->setRoutes($category_id, (array) $snapshot['routes'], false);
        }
        if (array_key_exists('og', $snapshot)) {
            (new shopCategoryOgModel())->set($category_id, (array) $snapshot['og']);
        }

        shopCategories::clear($category_id);
        $model->repair();

        return $category_id;
    }
}
