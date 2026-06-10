<?php

class shopCustom
{

    public static function resize($image, $w_o = false, $h_o = false){

        $image = str_replace(array('https://'.$_SERVER['SERVER_NAME'],'http://'.$_SERVER['SERVER_NAME'],'"'),'',$image);
        $image = $_SERVER['DOCUMENT_ROOT'].$image;

        if(file_exists($image)){


            if (($w_o < 0) || ($h_o < 0)) {
                echo "Некорректные входные параметры";
                return false;
            }
            list($w_i, $h_i, $type) = getimagesize($image); // Получаем размеры и тип изображения (число)
            $types = array("", "gif", "jpeg", "png"); // Массив с типами изображений
            $ext = $types[$type]; // Зная "числовой" тип изображения, узнаём название типа
            if ($ext) {
                $func = 'imagecreatefrom' . $ext; // Получаем название функции, соответствующую типу, для создания изображения
                $img_i = $func($image); // Создаём дескриптор для работы с исходным изображением
            } else {
                echo 'Некорректное изображение'; // Выводим ошибку, если формат изображения недопустимый
                return false;
            }
            /* Если указать только 1 параметр, то второй подстроится пропорционально */
            if (!$h_o) $h_o = $w_o / ($w_i / $h_i);
            if (!$w_o) $w_o = $h_o / ($h_i / $w_i);
            $img_o = imagecreatetruecolor($w_o, $h_o); // Создаём дескриптор для выходного изображения
            imagecopyresampled($img_o, $img_i, 0, 0, 0, 0, $w_o, $h_o, $w_i, $h_i); // Переносим изображение из исходного в выходное, масштабируя его
            $func = 'image' . $ext; // Получаем функция для сохранения результата
            return $func($img_o, $image); // Сохраняем изображение в тот же файл, что и исходное, возвращая результат этой операции

        }

    }

    public static function get_root_config($config){
        if(!$config)
            return false;

        return (wa()->getConfig()->getConfigFile('config')[$config]) ?: false;
    }

	public static function roistat_cookie($id = 'roistat_visit'){
        if(waRequest::cookie($id))
            return waRequest::cookie($id);
		else
			return 'info';
    }

	public static function categoriesHidden($id = 0, $depth = null, $tree = false, $params = false, $route = null){

        if ($id === true) {
            $id = 0;
            $tree = true;
        }


        if ($route && !is_array($route)) {
            $route = explode('/', $route, 2);
            $route = wa()->getRouting()->getRoute($route[0], isset($route[1]) ? $route[1] : null);
        }
        if (!$route) {
            $route = wa()->getRouting()->getRoute();
        }
        if (!$route) {
            return array();
        }

		$route['domain'] = wa()->getRouting()->getDomain(null, false, true);

        $cats = self::getTreeHidden($id, $depth, false, $route['domain'].'/'.$route['url']);

        return $cats;

	}

	public static function getTreeHidden($id, $depth = null, $escape = false, $route = null)
    {
		$model = new waModel();

        $where = array();
        if ($id) {

			$sql = "SELECT * FROM shop_category WHERE id = $id LIMIT 1";
			$parent = $model->query($sql)->fetchAssoc();

            $left = (int)$parent['left_key'];
            $right = (int)$parent['right_key'];
        } else {
            $left = $right = 0;
        }

        if (!$id && $depth === null && $route && ($cache = wa('shop')->getCache())) {
            $cache_key = waRouting::clearUrl($route);
            $data = $cache->get($cache_key, 'categories');
        }

        if (empty($data)) {

            $sql = "SELECT c.* FROM shop_category c";
            if ($id) {
                $where[] = "c.left_key >= i:left";
                $where[] = "c.right_key <= i:right";
            }
            if ($depth !== null) {
                $depth = max(0, intval($depth));
                if ($id && !empty($parent)) {
                    $depth += (int)$parent[$this->depth];
                }
                $where[] = "c.depth <= i:depth";
            }

            if ($route) {
                $sql .= " LEFT JOIN shop_category_routes cr ON c.id = cr.category_id";
                $where[] = "c.status = 0";
                $where[] = "cr.route IS NULL OR cr.route = '".$model->escape($route)."'";
            }
            if ($where) {
                $sql .= " WHERE (".implode(') AND (', $where).')';
            }
            $sql .= " ORDER BY c.left_key";

            $data = $model->query($sql, array('left' => $left, 'right' => $right, 'depth' => $depth))->fetchAll("id");
            if (!$id && $depth === null && $route && ifset($cache)) {
                $cache->set($cache_key, $data, 3600, 'categories');
            }
        }

        if ($escape) {
            foreach ($data as &$item) {
                $item['name'] = htmlspecialchars($item['name']);
            }
            unset($item);
        }
        return $data;
    }

	public static function xmlPercent($price, $percent){

        if($percent && $price){
            if($percent < 0){
                $percent = str_replace('-', '', $percent);
                $price = $price - round(($price / 100) * $percent, 2);
            }
            else{
                $price = $price + round(($price / 100) * $percent, 2);
            }
        }

        return round($price);
    }

	public static function array_sorting_by_field($data, $field, $sortBy = SORT_DESC){

		if(!$field)
			return $data;

		$volume  = array_column($data, $field);
		array_multisort($volume, $sortBy, $data);

		return $data;
	}

    public static function array_filter($array, $field){

        if(!is_array($array) || empty($field))
            return false;

        $data = array_filter($array, function ($k) use ($field){

            return preg_match("#$field#", $k);
			
        }, ARRAY_FILTER_USE_KEY);

        return ($data) ?: false;
    }

    public static function getTagsByCategory($id){

        $categoryModel = new shopCategoryModel();
        $category_params_model = new shopCategoryParamsModel();
        $categories = $categoryModel->getTree($id, null, false);

        if (!$categories) {
            return array();
        }

        $params_by_category = array();
        $rows = $category_params_model->getByField('category_id', array_keys($categories), true);
        foreach ($rows as $row) {
            $params_by_category[$row['category_id']][$row['name']] = $row['value'];
        }

        $data = array();
        foreach ($categories as $category) {
            if ($category['id'] == $id) {
                continue;
            }
            $params = isset($params_by_category[$category['id']])
                ? $params_by_category[$category['id']]
                : array();
            if (array_key_exists('menu', $params)) {
                $data[$category['id']] = $category;
            }
        }

        return $data;
    }

    public static function getCategoryUrlSlug($url)
    {
        if (preg_match('#/category/([^/]+)/?#', $url, $m)) {
            return $m[1];
        }
        return 'default';
    }

    public static function subcategoriesFilters($id){

        $map = self::subcategoriesFiltersByIds(array($id));
        return !empty($map[$id]);
    }

    /**
     * Batch check: which subcategories have products matching current GET filters.
     * One SQL instead of N× shopProductsCollection::count().
     *
     * @param int[] $category_ids
     * @return array [category_id => bool]
     */
    public static function subcategoriesFiltersByIds(array $category_ids)
    {
        static $cache = array();

        $category_ids = array_values(array_unique(array_map('intval', $category_ids)));
        $category_ids = array_filter($category_ids);
        if (!$category_ids) {
            return array();
        }

        $get = waRequest::get();
        unset($get['page'], $get['sort'], $get['order'], $get['_']);

        $cache_key = md5(serialize($category_ids).serialize($get));
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $feature_model = new shopFeatureModel();
        $features = $feature_model->getByField('code', array_keys($get), 'code');

        $filter_get = array();
        foreach ($get as $code => $values) {
            if (isset($features[$code])) {
                $filter_get[$code] = $values;
            }
        }

        $has_price = (isset($get['price_min']) && $get['price_min'] !== '')
            || (isset($get['price_max']) && $get['price_max'] !== '');
        $has_stock = !empty($get['in_stock_only']) || !empty($get['out_of_stock_only']);

        if (!$filter_get && !$has_price && !$has_stock) {
            return $cache[$cache_key] = array_fill_keys($category_ids, true);
        }

        if ($has_price || $has_stock) {
            $result = array();
            foreach ($category_ids as $id) {
                $result[$id] = self::subcategoriesFiltersViaCollection($id);
            }
            return $cache[$cache_key] = $result;
        }

        if (!$features) {
            return $cache[$cache_key] = array_fill_keys($category_ids, true);
        }

        $joins = '';
        $where = array('cp.category_id IN (i:category_ids)');
        $params = array('category_ids' => $category_ids);
        $alias_index = 1;

        if (wa()->getEnv() == 'frontend') {
            $where[] = 'p.status = 1';
        }

        foreach ($filter_get as $feature_code => $values) {
            if (!is_array($values)) {
                if ($values === '') {
                    continue;
                }
                $values = array($values);
            }
            if (isset($values['min']) || isset($values['max']) || isset($values['unit'])) {
                continue;
            }
            $value_ids = array();
            foreach ($values as $v) {
                $value_ids[] = (int) $v;
            }
            $value_ids = array_filter($value_ids);
            if (!$value_ids) {
                return $cache[$cache_key] = array_fill_keys($category_ids, false);
            }
            $alias = 'pf'.($alias_index++);
            $joins .= ' JOIN shop_product_features '.$alias.' ON '.$alias.'.product_id = p.id'
                .' AND '.$alias.'.feature_id = '.(int) $features[$feature_code]['id']
                .' AND '.$alias.'.feature_value_id IN ('.implode(',', $value_ids).')'
                .' AND '.$alias.'.sku_id IS NULL';
        }

        if (!$joins) {
            return $cache[$cache_key] = array_fill_keys($category_ids, true);
        }

        $sql = 'SELECT DISTINCT cp.category_id FROM shop_category_products cp'
            .' JOIN shop_product p ON p.id = cp.product_id'
            .$joins
            .' WHERE '.implode(' AND ', $where);

        $db = new waModel();
        $found = $db->query($sql, $params)->fetchAll(null, true);
        $found = array_flip($found);

        $result = array();
        foreach ($category_ids as $id) {
            $result[$id] = isset($found[$id]);
        }

        return $cache[$cache_key] = $result;
    }

    protected static function subcategoriesFiltersViaCollection($id)
    {
        $collection = new shopProductsCollection('category/'.$id);
        $collection->filters(waRequest::get());

        return (bool) $collection->count();
    }


    public static function getSeoField($data = [], $type, $field = 'h1'){
        if(!isset($data['id']))
            return false;
        else
            $id = $data['id'];

        $table = [];
        if($type == 'product'){
            $table['table'] = 'shop_seo_product_settings';
            $table['pivot'] = 'product_id';
        }elseif($type = 'category'){
            $table['table'] = 'shop_seo_category_settings';
            $table['pivot'] = 'category_id';
        }else
            return false;

        $db = new waModel();
		
		if(isset($table['table']) == false || isset($table['pivot']) == false)
			return false;

        $sql = "SELECT value AS ". $field ." FROM ". $table['table'] ." WHERE ". $table['pivot'] ." = ". $id ." AND name = '". $field ."'";
        $value = $db->query($sql)->fetchAssoc();
        if (!$value) {
            return false;
        }

        return $value;
    }

    /**
     * Batch-load product params for list views (one query instead of N product() calls).
     *
     * @param int[] $product_ids
     * @return array [product_id => [param_name => param_value]]
     */
    public static function getProductParamsByIds($product_ids)
    {
        if (!$product_ids) {
            return array();
        }

        $product_params_model = new shopProductParamsModel();
        $rows = $product_params_model->getByField('product_id', $product_ids, true);

        $params = array();
        foreach ($rows as $row) {
            $params[$row['product_id']][$row['name']] = $row['value'];
        }

        return $params;
    }

    public static function getListProducts($ids, $title = '', $view = 'thumbs', $cart = false){

        $ids = json_decode($ids, true);

        if(!$ids)
            return false;

        $arKeys = array_keys($ids);
        $collection = new shopProductsCollection($arKeys);
        $products = $collection->getProducts("*,image_crop_small");

        uksort($products, function ($leftItem, $rightItem) use ($arKeys){

            $order = array_flip($arKeys);

            $leftPos = $order[$leftItem];
            $rightPos = $order[$rightItem];

            return $leftPos >= $rightPos;
        });

        foreach ($ids as $key => $val){

            if(isset($products[$key])){

                if($val[0])
                    $products[$key]['list_name'] = $val[0];

                if($val[1])
                    $products[$key]['summary'] = $val[1];
            }
        }

        $product = [
            'related_view' => $view, //thumbs,list,short-list
        ];

        if($cart)
            $product['cart_view'] = $cart;

        $view = wa()->getView();
        $view->assign('pages_count', false);
        $view->assign('product', $product);
        $view->assign('products', $products);
        $html = $view->fetch(wa()->getDataPath('themes', true, 'shop') . '/osnovnaja_new_header_footer_form/list-thumbs.html');

        if($title)
            $html = '<div class="h3">'. $title .'</div>'.$html;

        return $html;
    }

}
