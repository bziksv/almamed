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

	/**
	 * Дерево категорий для sidebar — кэш 1 ч (getTree + params + tree build).
	 */
	public static function getCachedShopCategoriesTree()
	{
		static $tree = null;
		if ($tree !== null) {
			return $tree;
		}

		if (!wa()->appExists('shop')) {
			return array();
		}

		$routing = wa()->getRouting();
		$route = $routing->getRoute();
		$domain = $routing->getDomain(null, true);
		$cache_key = 'sidebar_tree_' . md5($domain . '/' . ifset($route, 'url', ''));
		$cache = new waSerializeCache($cache_key, 3600, 'shop/sidebar_categories');

		if ($cache->isCached()) {
			$tree = $cache->get();
			return is_array($tree) ? $tree : array();
		}

		wa('shop', 1);
		$tree = (new shopViewHelper(wa('shop')))->categories(0, 0, true, true);
		$cache->set($tree);

		return is_array($tree) ? $tree : array();
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

    /**
     * Oftalmologiya catalog layout modes: v1 (default list), v2 (group by brand), v3 (reserved).
     * Root category id = 16 (full_url oftalmologiya).
     * Guests / buyers always see v1 (обкатка только среди своих).
     * Switcher + saved setting: backend-пользователи (Контакты → Пользователи, is_user=1).
     */
    const OFTALM_ROOT_CATEGORY_ID = 16;
    const OFTALM_LAYOUT_SETTING = 'oftalm_catalog_layout';

    public static function isOftalmCategoryTree($root_category_id)
    {
        return (int)$root_category_id === self::OFTALM_ROOT_CATEGORY_ID;
    }

    public static function canManageOftalmLayout()
    {
        try {
            $user = wa()->getUser();
        } catch (Exception $e) {
            return false;
        }
        if (!$user || !$user->isAuth()) {
            return false;
        }
        // Contacts → Users (wa_contact.is_user = 1), not shop customers
        return (int)$user['is_user'] === 1;
    }

    public static function getOftalmLayoutDefault()
    {
        $layout = wa()->getSetting(self::OFTALM_LAYOUT_SETTING, 'v1', 'shop');
        if (!in_array($layout, array('v1', 'v2', 'v3'), true)) {
            return 'v1';
        }
        return $layout;
    }

    /**
     * Resolve oftalm layout.
     * Guests / buyers → always v1.
     * Backend users → GET/cookie + shared setting among staff (oftalm_save).
     *
     * @param int $root_category_id
     * @return string v1|v2|v3 or '' if not oftalm tree
     */
    public static function getOftalmLayout($root_category_id)
    {
        if (!self::isOftalmCategoryTree($root_category_id)) {
            return '';
        }

        // Public: always classic list while we test v2 internally
        if (!self::canManageOftalmLayout()) {
            return 'v1';
        }

        $default = self::getOftalmLayoutDefault();

        $layout = waRequest::get('oftalm_layout', null, waRequest::TYPE_STRING_TRIM);
        if ($layout === null || $layout === '') {
            $layout = waRequest::cookie('shop_oftalm_layout', $default, waRequest::TYPE_STRING_TRIM);
        }
        if (!in_array($layout, array('v1', 'v2', 'v3'), true)) {
            $layout = $default;
        }

        // Shared default among backend users only (not for the storefront public)
        if (waRequest::get('oftalm_save') && in_array($layout, array('v1', 'v2'), true)) {
            $asm = new waAppSettingsModel();
            $asm->set('shop', self::OFTALM_LAYOUT_SETTING, $layout);
        }

        return $layout;
    }

    /**
     * Group products by brand (feature brend / productbrands), sort brands A→Z,
     * within each brand sort by price asc (zero / «по запросу» last).
     *
     * @param array $products
     * @return array [ brandName => ['name'=>,'url'=>,'id'=>,'products'=>[]] ]
     */
    public static function groupProductsByBrand($products)
    {
        if (!$products) {
            return array();
        }

        if (class_exists('shopProductbrandsPlugin')) {
            $products = shopProductbrandsPlugin::prepareProducts($products);
        }

        $groups = array();
        foreach ($products as $id => $p) {
            $brand_name = 'Без бренда';
            $brand_url = '';
            $brand_id = 0;
            if (!empty($p['brand']['name'])) {
                $brand_name = $p['brand']['name'];
                $brand_url = ifset($p['brand']['url'], '');
                $brand_id = (int)ifset($p['brand']['id'], 0);
            }
            if (!isset($groups[$brand_name])) {
                $groups[$brand_name] = array(
                    'name' => $brand_name,
                    'url' => $brand_url,
                    'id' => $brand_id,
                    'products' => array(),
                );
            }
            $groups[$brand_name]['products'][$id] = $p;
        }

        uksort($groups, function ($a, $b) {
            if ($a === 'Без бренда') {
                return 1;
            }
            if ($b === 'Без бренда') {
                return -1;
            }
            return strcasecmp($a, $b);
        });

        foreach ($groups as &$group) {
            uasort($group['products'], function ($a, $b) {
                $pa = isset($a['price']) ? (float)$a['price'] : 0;
                $pb = isset($b['price']) ? (float)$b['price'] : 0;
                if ($pa <= 0 && $pb > 0) {
                    return 1;
                }
                if ($pb <= 0 && $pa > 0) {
                    return -1;
                }
                if ($pa == $pb) {
                    return 0;
                }
                return ($pa < $pb) ? -1 : 1;
            });

            $count = count($group['products']);
            $group['count'] = $count;
            $priced = array();
            foreach ($group['products'] as $p) {
                $price = isset($p['price']) ? (float)$p['price'] : 0;
                if ($price > 0) {
                    $priced[] = $price;
                }
            }
            $group['price_from'] = $priced ? min($priced) : 0;
            $group['price_to'] = $priced ? max($priced) : 0;
            $mod = $count % 10;
            $mod100 = $count % 100;
            if ($mod == 1 && $mod100 != 11) {
                $group['count_label'] = $count.' модель';
            } elseif ($mod >= 2 && $mod <= 4 && ($mod100 < 12 || $mod100 > 14)) {
                $group['count_label'] = $count.' модели';
            } else {
                $group['count_label'] = $count.' моделей';
            }
        }
        unset($group);

        return $groups;
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

    /** Datagon sticker feature codes (boolean) — local Almamed. */
    const DG_STICKER_CODES = array(
        'dg_sticker_not_supplied',
        'dg_sticker_discontinued',
        'dg_sticker_no_ru',
        'dg_sticker_sale',
        'dg_sticker_has_ru',
        'dg_sticker_in_stock',
    );

    public static function dgStickerLabels()
    {
        return array(
            'dg_sticker_not_supplied' => 'Не поставляется',
            'dg_sticker_discontinued' => 'Снят / аналог',
            'dg_sticker_no_ru' => 'Нет РУ',
            'dg_sticker_sale' => 'Скидка',
            'dg_sticker_has_ru' => 'Есть РУ',
            'dg_sticker_in_stock' => 'Есть в наличии',
        );
    }

    public static function isDgStickerFeatureCode($code)
    {
        $code = (string)$code;
        return strpos($code, 'dg_sticker_') === 0;
    }

    /**
     * Active stickers for products (boolean Yes = feature_value_id=1).
     *
     * @param int[] $product_ids
     * @return array [product_id => ['codes' => string[], 'sale_until' => string]]
     */
    public static function getDgStickersByProductIds($product_ids)
    {
        $out = array();
        $product_ids = array_values(array_unique(array_filter(array_map('intval', (array)$product_ids))));
        if (!$product_ids) {
            return $out;
        }

        $fm = new shopFeatureModel();
        $features = $fm->getByField('code', self::DG_STICKER_CODES, true);
        if (!$features) {
            return $out;
        }
        $id_to_code = array();
        foreach ($features as $f) {
            $id_to_code[(int)$f['id']] = $f['code'];
        }
        if (!$id_to_code) {
            return $out;
        }

        $pfm = new shopProductFeaturesModel();
        $rows = $pfm->getByField(
            array(
                'product_id' => $product_ids,
                'feature_id' => array_keys($id_to_code),
                'sku_id' => null,
            ),
            true
        );
        $active = array();
        foreach ($rows as $row) {
            if ((int)$row['feature_value_id'] !== 1) {
                continue;
            }
            $pid = (int)$row['product_id'];
            $code = ifset($id_to_code[(int)$row['feature_id']]);
            if ($code) {
                $active[$pid][$code] = true;
            }
        }

        $sale_until_by_pid = array();
        $until_feature = $fm->getByField('code', 'dg_sticker_sale_until');
        if ($until_feature && !empty($until_feature['id'])) {
            $until_fid = (int)$until_feature['id'];
            $until_rows = $pfm->getByField(
                array(
                    'product_id' => $product_ids,
                    'feature_id' => $until_fid,
                    'sku_id' => null,
                ),
                true
            );
            $value_ids = array();
            foreach ($until_rows as $row) {
                $vid = (int)$row['feature_value_id'];
                if ($vid > 0) {
                    $value_ids[$vid] = true;
                }
            }
            $id_to_val = array();
            if ($value_ids) {
                $vm = new shopFeatureValuesVarcharModel();
                $vals = $vm->getByField('id', array_keys($value_ids), true);
                foreach ($vals as $v) {
                    $id_to_val[(int)$v['id']] = self::normalizeDgSaleUntilLabel(ifset($v['value'], ''));
                }
            }
            foreach ($until_rows as $row) {
                $pid = (int)$row['product_id'];
                $label = ifset($id_to_val[(int)$row['feature_value_id']], '');
                if ($label !== '') {
                    $sale_until_by_pid[$pid] = $label;
                }
            }
        }

        foreach ($product_ids as $pid) {
            $codes = array();
            foreach (self::DG_STICKER_CODES as $code) {
                if (!empty($active[$pid][$code])) {
                    $codes[] = $code;
                }
            }
            if ($codes) {
                $out[$pid] = array(
                    'codes' => $codes,
                    'sale_until' => ifset($sale_until_by_pid[$pid], ''),
                );
            }
        }
        return $out;
    }

    /**
     * HTML stack of sticker badges for corner (empty string if none).
     *
     * @param string[]|array|null $codes список code или pack ['codes'=>[], 'sale_until'=>'']
     * @param string|null $sale_until срок акции дд-мм-гггг (Feature dg_sticker_sale_until)
     * @return string
     */
    public static function dgStickerBadgesHtml($codes, $sale_until = null)
    {
        if (empty($codes) || !is_array($codes)) {
            return '';
        }
        if (isset($codes['codes']) && is_array($codes['codes'])) {
            if ($sale_until === null) {
                $sale_until = ifset($codes['sale_until'], '');
            }
            $codes = $codes['codes'];
        }
        if (empty($codes)) {
            return '';
        }
        $labels = self::dgStickerLabels();
        $until = self::normalizeDgSaleUntilLabel($sale_until);
        $parts = array();
        foreach (self::DG_STICKER_CODES as $code) {
            if (!in_array($code, $codes, true)) {
                continue;
            }
            $label = ifset($labels[$code], $code);
            if ($code === 'dg_sticker_sale' && $until !== '') {
                $label = 'Скидка до ' . $until;
            }
            $class = 'dg-sticker dg-sticker--' . str_replace('dg_sticker_', '', $code);
            $parts[] = '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"><span>'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</span></div>';
        }
        if (!$parts) {
            return '';
        }
        return '<div class="dg-sticker-stack">' . implode('', $parts) . '</div>';
    }

    /**
     * Срок акции из features (varchar dg_sticker_sale_until) → «дд-мм-гггг» или ''.
     *
     * @param array $features
     * @return string
     */
    public static function dgStickerSaleUntilFromFeatures($features)
    {
        if (empty($features) || !is_array($features) || !array_key_exists('dg_sticker_sale_until', $features)) {
            return '';
        }
        return self::normalizeDgSaleUntilLabel($features['dg_sticker_sale_until']);
    }

    /**
     * @param mixed $raw
     * @return string
     */
    public static function normalizeDgSaleUntilLabel($raw)
    {
        if ($raw === null || $raw === false || $raw === '') {
            return '';
        }
        if (is_object($raw)) {
            if (isset($raw['value'])) {
                $raw = $raw['value'];
            } elseif (method_exists($raw, '__toString')) {
                $raw = (string)$raw;
            } else {
                return '';
            }
        }
        if (is_array($raw)) {
            $raw = ifset($raw['value'], ifset($raw[0], ''));
        }
        $s = trim((string)$raw);
        if ($s === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^(\d{2})[.\-\/](\d{2})[.\-\/](\d{4})$/', $s, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        return $s;
    }

    /**
     * Resolve sticker codes from product features map (frontend $product.features).
     *
     * @param array $features code => value
     * @return string[]
     */
    public static function dgStickerCodesFromFeatures($features)
    {
        if (empty($features) || !is_array($features)) {
            return array();
        }
        $codes = array();
        foreach (self::DG_STICKER_CODES as $code) {
            if (!array_key_exists($code, $features)) {
                continue;
            }
            $v = $features[$code];
            $yes = false;
            if ($v instanceof shopBooleanValue) {
                $yes = !empty($v['value']);
            } elseif (is_object($v) && isset($v['value'])) {
                $yes = !empty($v['value']);
            } elseif (is_bool($v)) {
                $yes = $v;
            } elseif (is_numeric($v)) {
                $yes = (int)$v === 1;
            } elseif (is_string($v)) {
                $low = mb_strtolower(trim($v));
                $yes = in_array($low, array('1', 'да', 'yes', 'true'), true);
            } else {
                $yes = !empty($v);
            }
            if ($yes) {
                $codes[] = $code;
            }
        }
        return $codes;
    }

    /**
     * Badges from full features map (boolean stickers + срок «Скидка до»).
     *
     * @param array $features
     * @return string
     */
    public static function dgStickerBadgesHtmlFromFeatures($features)
    {
        return self::dgStickerBadgesHtml(
            self::dgStickerCodesFromFeatures($features),
            self::dgStickerSaleUntilFromFeatures($features)
        );
    }

}
