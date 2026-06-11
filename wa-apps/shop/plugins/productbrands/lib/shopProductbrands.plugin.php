<?php

/**
 * @author wa-apps.ru <info@wa-apps.ru>
 * @copyright 2013-2016 wa-apps.ru
 * @license Webasyst License http://www.webasyst.ru/terms/#eula
 * @link http://www.webasyst.ru/store/plugin/shop/productbrands/
 */
class shopProductbrandsPlugin extends shopPlugin
{
    /**
     * @var array
     */
    protected static $feature;

    /**
     * @var array|null Sorted brand IDs for current request (frontend list).
     */
    protected static $sorted_brand_ids;

    /**
     * @var array|null
     */
    protected static $brand_list_meta;

    /**
     * @var array|null
     */
    protected static $brand_counts_cache;

    /**
     * @var array|null
     */
    protected static $brands;

    /**
     * @return string
     */
    public function frontendNav()
    {
        if ($this->getSettings('hook') == 'frontend_nav') {
            return $this->nav();
        }
    }

    /**
     * @return string
     * @throws waException
     */
    public static function whichUi() {
        if (method_exists(wa(), 'whichUI')) {
            return wa()->whichUI();
        } else {
            return '1.3';
        }
    }

    /**
     * @return string
     */
    public function frontendNavAux()
    {
        if ($this->getSettings('hook') == 'frontend_nav_aux') {
            return $this->nav();
        }
    }

    protected function nav()
    {
        $brands = self::getBrands();
        if (!$brands) {
            return;
        }
        $view = wa()->getView();
        $view->assign('brands', $brands);
        if ($t_nav = $this->getSettings('template_nav')) {
            return $view->fetch('string:'.$t_nav);
        } else {
            return $view->fetch($this->path.'/templates/frontendNav.html');
        }
    }


    /**
     * @param $params
     * @return array
     */
    public function backendProducts($params)
    {
        if (self::whichUi() == '2.0') {
            $this->addJs('js/productbrands.js');
        }
        if (!$params) {
            $view = wa()->getView();
            return array(
                'sidebar_top_li' => '<li id="s-productbrands">
                    <a href="#/brands/"><i class="icon16" style="background-image: url('.$this->getPluginStaticUrl().'img/brands.png);"></i>'._wp('Brands').'</a>
                    <script src="'.$this->getPluginStaticUrl().'js/productbrands.js"></script>
                    </li>',
                'sidebar_section' => $view->fetch($this->path.'/templates/backendProducts.html')
            );
        } elseif (!empty($params['type']) && substr($params['type'], 0, 6) == 'brand_') {
            $hash = str_replace('brand_', 'brand/', waRequest::get('hash'));
            return array(
                'title_suffix' => '<span class="s-product-list-manage"><a href="#/'.$hash.'" class="gray"><i class="icon16 settings"></i>'._w('Settings').'</a></span>'
            );
        }
    }

    public function backendExtendedMenu(&$params)
    {
        $params['menu']['catalog']['submenu'][] = array(
            "url" => wa('shop')->getAppUrl(null, true) . '?action=products#brands',
            "name" => _wp('Brands'),
        );
    }

    /**
     * @param int $category_id
     * @return array
     */
    public static function getCategoryBrands($category_id)
    {
        $collection = new shopProductbrandsPluginCollection('category/'.$category_id);
        return $collection->getBrands();
    }

    /**
     * Returns brand feature
     * @return array
     */
    protected static function getFeature()
    {
        if (self::$feature === null) {
            self::$feature = array();
            $feature_id = wa()->getSetting('feature_id', null, array('shop', 'productbrands'));
            if ($feature_id) {
                $feature_model = new shopFeatureModel();
                if ($feature = $feature_model->getById($feature_id)) {
                    self::$feature = $feature;
                }
            }
        }
        return self::$feature;
    }

    /**
     * Returns brands of the product
     *
     * @param int $product_id
     * @param bool $all
     * @return array
     */
    public static function productBrand($product_id, $all = false)
    {
        $feature = self::getFeature();
        if ($feature) {
            $product_features_model = new shopProductFeaturesModel();
            $row = $product_features_model->getByField(array(
                'product_id' => $product_id, 'feature_id' => $feature['id'], 'sku_id' => null
            ), $all);
            $brand_model = new shopProductbrandsModel();
            if ($row) {
                if ($all) {
                    $brands = array();
                    foreach ($row as $r) {
                        $brand = $brand_model->getBrand($r['feature_value_id']);
                        $brand_url = $brand['url'] ? $brand['url'] : urlencode($brand['name']);
                        $brand['url'] = wa()->getRouteUrl('shop/frontend/brand', array('brand' => $brand_url));
                        $brands[] = $brand;
                    }
                    return $brands;
                } else {
                    $brand = $brand_model->getBrand($row['feature_value_id']);
                    $brand_url = $brand['url'] ? $brand['url'] : urlencode($brand['name']);
                    $brand['url'] = wa()->getRouteUrl('shop/frontend/brand', array('brand' => $brand_url));
                    return $brand;
                }
            }
        }
        return array();
    }

    /**
     * @param array $products
     * @return array
     */
    public static function prepareProducts($products)
    {
        $feature = self::getFeature();
        if (!$products || !$feature) {
            return $products;
        }
        $brands = self::getBrands();
        $product_features_model = new shopProductFeaturesModel();
        $rows = $product_features_model->getByField(array('product_id' => array_keys($products), 'feature_id' => $feature['id'], 'sku_id' => null), true);
        foreach ($rows as $row) {
            $brand_id = $row['feature_value_id'];
            if (isset($brands[$brand_id])) {
                $products[$row['product_id']]['brand'] = $brands[$brand_id];
            }
        }
        return $products;
    }

    /**
     * @param boolean $with_count
     * @return array
     */
    public static function getBrands($with_count = null)
    {
        if (self::$brands === null) {
            self::$brands = self::getBrandsPage(0, null, $with_count);
        }
        return self::$brands;
    }

    /**
     * Total number of visible brands (same filters as getBrands()).
     *
     * @param boolean $with_count
     * @return int
     */
    public static function getBrandsCount($with_count = null)
    {
        return count(self::getSortedBrandIds($with_count));
    }

    /**
     * Load one page of brands without building full in-memory list.
     *
     * @param int $offset
     * @param int|null $limit null = all
     * @param boolean $with_count
     * @return array
     */
    public static function getBrandsPage($offset, $limit = null, $with_count = null)
    {
        $ids = self::getSortedBrandIds($with_count);
        if ($limit !== null) {
            $ids = array_slice($ids, $offset, $limit);
        } elseif ($offset) {
            $ids = array_slice($ids, $offset);
        }
        return self::loadBrandsByIds($ids, $with_count);
    }

    protected static function getBrandListCacheKey()
    {
        $parts = array(
            wa()->getSetting('sort', null, array('shop', 'productbrands')),
            wa('shop')->getPlugin('productbrands')->getSettings('products'),
            wa()->getEnv(),
        );
        if (wa()->getEnv() == 'frontend' && waRequest::param('type_id')) {
            $parts[] = waRequest::param('type_id');
        }
        $feature = self::getFeature();
        $parts[] = $feature ? $feature['id'] : 0;

        return md5(serialize($parts));
    }

    /**
     * Список value_id => name для фичи «бренд» — отдельный кэш, чтобы cold /brands/ не тянул 1500+ values каждый раз.
     *
     * @param array $feature
     * @param shopFeatureModel $feature_model
     * @return array
     */
    protected static function getCachedBrandFeatureValues($feature, shopFeatureModel $feature_model)
    {
        if (wa()->getEnv() != 'frontend') {
            return $feature_model->getFeatureValues($feature);
        }

        $cache = new waSerializeCache(
            'feature_values_' . $feature['id'],
            86400,
            'shop/productbrands'
        );
        if ($cache->isCached()) {
            $values = $cache->get();
            return is_array($values) ? $values : array();
        }

        $values = $feature_model->getFeatureValues($feature);
        $cache->set($values);

        return $values;
    }

    /**
     * @param boolean $with_count
     * @return int[]
     */
    protected static function getSortedBrandIds($with_count = null)
    {
        if (self::$sorted_brand_ids !== null) {
            return self::$sorted_brand_ids;
        }

        if (wa()->getEnv() == 'frontend') {
            $cache = new waSerializeCache(
                'sorted_ids_' . self::getBrandListCacheKey(),
                3600,
                'shop/productbrands'
            );
            if ($cache->isCached()) {
                $cached = $cache->get();
                self::$sorted_brand_ids = $cached['ids'];
                self::$brand_counts_cache = $cached['counts'];
                self::$brand_list_meta = array(
                    'names' => $cached['names'],
                    'counts' => $cached['counts'],
                    'feature' => self::getFeature(),
                    'products_settings' => wa('shop')->getPlugin('productbrands')->getSettings('products'),
                );
                return self::$sorted_brand_ids;
            }
        }

        $meta = self::fetchBrandListMeta($with_count);
        $brand_names = $meta['names'];
        $counts = $meta['counts'];
        $products_settings = $meta['products_settings'];

        self::$brand_counts_cache = $counts;

        if (!$brand_names) {
            self::$sorted_brand_ids = array();
            return self::$sorted_brand_ids;
        }

        $hidden_ids = array();
        if (wa()->getEnv() == 'frontend') {
            $brands_model = new shopProductbrandsModel();
            $hidden_ids = $brands_model->query(
                'SELECT id FROM ' . $brands_model->getTableName() . ' WHERE hidden = 1 AND id IN (i:ids)',
                array('ids' => array_keys($brand_names))
            )->fetchAll(null, true);
            $hidden_ids = array_flip($hidden_ids);
        }

        $ids = array();
        foreach ($brand_names as $id => $name) {
            if (wa()->getEnv() == 'frontend' && $products_settings !== 'all' && !isset($counts[$id])) {
                continue;
            }
            if (wa()->getEnv() == 'frontend' && isset($hidden_ids[$id])) {
                continue;
            }
            $ids[] = $id;
        }

        if ($ids && wa()->getSetting('sort', null, array('shop', 'productbrands'))) {
            $sort_names = array();
            foreach ($ids as $id) {
                $sort_names[$id] = $brand_names[$id];
            }
            natcasesort($sort_names);
            $ids = array_keys($sort_names);
        }

        self::$sorted_brand_ids = $ids;

        if (wa()->getEnv() == 'frontend') {
            $cache = new waSerializeCache(
                'sorted_ids_' . self::getBrandListCacheKey(),
                3600,
                'shop/productbrands'
            );
            $cache->set(array(
                'ids' => $ids,
                'names' => $brand_names,
                'counts' => $counts,
            ));
        }

        return self::$sorted_brand_ids;
    }

    /**
     * @param boolean $with_count
     * @return array{names: array, counts: array, feature: array, products_settings: string}
     */
    protected static function fetchBrandListMeta($with_count = null)
    {
        if (self::$brand_list_meta !== null) {
            return self::$brand_list_meta;
        }

        $feature = self::getFeature();
        $products_settings = wa('shop')->getPlugin('productbrands')->getSettings('products');
        $brand_names = array();
        $counts = array();

        if ($feature) {
            $feature_model = new shopFeatureModel();
            $brand_names = self::getCachedBrandFeatureValues($feature, $feature_model);
            $product_features_model = new shopProductFeaturesModel();
            $types = array();
            if (wa()->getEnv() == 'frontend' && waRequest::param('type_id') && is_array(waRequest::param('type_id'))) {
                $types = waRequest::param('type_id');
            }
            if ($products_settings == 'all' || wa()->getEnv() != 'frontend') {
                if ($with_count) {
                    $sql = "SELECT feature_value_id, COUNT(*) FROM " . $product_features_model->getTableName(). "
                        WHERE feature_id = i:0 AND sku_id IS NULL
                        GROUP BY feature_value_id";
                    $counts = $product_features_model->query($sql, $feature['id'])->fetchAll('feature_value_id', true);
                }
            } else {
                $sql = "SELECT feature_value_id, COUNT(*) FROM " . $product_features_model->getTableName() . " pf
                    JOIN shop_product p ON pf.product_id = p.id
                    WHERE pf.feature_id = i:0 AND pf.sku_id IS NULL " . (wa()->getEnv() == 'frontend' && $products_settings == 'published' ? "AND p.status = 1 " : '') .
                    ($types ? 'AND p.type_id IN (i:1) ' : '') .
                    "GROUP BY pf.feature_value_id";
                $counts = $product_features_model->query($sql, $feature['id'], $types)->fetchAll('feature_value_id', true);
            }
        }

        self::$brand_list_meta = array(
            'names' => $brand_names,
            'counts' => $counts,
            'feature' => $feature,
            'products_settings' => $products_settings,
        );

        return self::$brand_list_meta;
    }

    /**
     * @param int[] $ids
     * @param boolean $with_count
     * @return array
     */
    protected static function loadBrandsByIds(array $ids, $with_count = null)
    {
        if (!$ids) {
            return array();
        }

        $meta = self::fetchBrandListMeta($with_count);
        $brand_names = $meta['names'];
        $counts = self::$brand_counts_cache !== null ? self::$brand_counts_cache : $meta['counts'];
        $feature = $meta['feature'];

        if (!$feature) {
            return array();
        }

        $brands_model = new shopProductbrandsModel();
        $rows = $brands_model->getById($ids);
        $brands = array();

        if (wa()->getEnv() == 'frontend') {
            $brand_path = wa('shop')->getPlugin('productbrands')->getSettings('url');
            if ($brand_path) {
                $url = wa()->getRouteUrl('shop/frontend') . $brand_path. '/%BRAND%/';
            } else {
                $routing_path = wa()->getAppPath('plugins/productbrands/lib/config/routing.php', 'shop');
                $routing = include($routing_path);
                $url = wa()->getRouteUrl('shop/frontend') . 'brand/%BRAND%/';
                foreach ($routing as $k => $v) {
                    if ($v == 'frontend/brand') {
                        $url = wa()->getRouteUrl('shop/frontend') . str_replace('<brand>', '%BRAND%', $k);
                        break;
                    }
                }
            }
        }

        foreach ($ids as $id) {
            if (!isset($brand_names[$id])) {
                continue;
            }
            $name = $brand_names[$id];
            if (isset($rows[$id])) {
                $brands[$id] = $rows[$id];
                $brands[$id]['name'] = $name;
                $brands[$id]['params'] = shopProductbrandsModel::getParams($brands[$id]['params']);
            } else {
                $brands[$id] = array(
                    'id' => $id,
                    'name' => $name,
                    'summary' => '',
                    'description' => '',
                    'image' => null,
                    'url' => null,
                    'filter' => '',
                    'hidden' => 0,
                    'params' => array()
                );
            }
            if (wa()->getEnv() == 'frontend') {
                $brand_url = $brands[$id]['url'] ? $brands[$id]['url'] : urlencode($name);
                $brands[$id]['url'] = str_replace('%BRAND%', $brand_url, $url);
            }
            $brands[$id]['count'] = isset($counts[$id]) ? $counts[$id] : 0;
        }

        if ($brands && wa()->appExists('mylang') && wa()->getEnv() == 'frontend') {
            $mylang_params = array('appId' => 'shop', 'type' => 'feature_value', 'ids' => array_keys($brands), 'format' => true);
            $mylang_translates = wa()->event(array('mylang', 'external_plugin_getdata'), $mylang_params);
            if ($mylang_translates && !empty($mylang_translates['mylang'])) {
                foreach ($mylang_translates['mylang'] as $t_id => $t) {
                    if (!empty($brands[$t_id]) && !empty($t[$feature['type']])) {
                        $brands[$t_id]['name'] = $t[$feature['type']];
                    }
                }
            }
        }

        return $brands;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected static function sortBrands($a, $b)
    {
        if ($a['name'] == $b['name']) {
            return 0;
        }
        return ($a['name'] < $b['name']) ? -1 : 1;
    }

    public function productsCollection($params)
    {
        /**
         * @var shopProductsCollection $collection
         */
        $collection = $params['collection'];
        $hash = $collection->getHash();
        if (is_array($hash) && count($hash) == 1 && substr($hash[0], 0, 6) == 'brand_') {
            $hash = explode('_', $hash[0], 2);
        }
        if ($hash[0] !== 'brand') {
            return null;
        }
        $feature_id = (int)wa()->getSetting('feature_id', null, array('shop', 'productbrands'));
        if ($feature_id) {
            $varchar_model = new shopFeatureValuesVarcharModel();
            $v = $varchar_model->getById($hash[1]);
            $collection->addTitle($v['value']);
            $collection->addJoin('shop_product_features', null, ':table.feature_id = '.$feature_id.' AND :table.feature_value_id = '.(int)$hash[1]);
            return true;
        }
    }


    /**
     * @param array $route
     * @return array
     */
    public function sitemap($route)
    {
        $feature_id = $this->getSettings('feature_id');
        $feature_model = new shopFeatureModel();
        $feature = $feature_model->getById($feature_id);
        if (!$feature) {
            return;
        }
        $values = $feature_model->getFeatureValues($feature);

        if (!empty($route['type_id']) && is_array($route['type_id'])) {
            $types = $route['type_id'];
        } else {
            $types = array();
        }

        $brands_model = new shopProductbrandsModel();
        $brands = $brands_model->getAll('id');

        $existed = $this->getByTypes($feature['id'], $types);

        $urls = array();
        $brand_url = wa()->getRouteUrl('shop/frontend/brand', array('brand' => '%BRAND%'), true);
        foreach ($values as $v_id => $v) {
            if (in_array($v_id, $existed)) {
                if (isset($brands[$v_id])) {
                    if ($brands[$v_id]['hidden']) {
                        continue;
                    }
                    if (!empty($brands[$v_id]['url'])) {
                        $v = $brands[$v_id]['url'];
                    }
                }
                $urls[] = array(
                    'loc' => str_replace('%BRAND%', str_replace('%2F', '/', urlencode($v)), $brand_url),
                    'changefreq' => waSitemapConfig::CHANGE_MONTHLY,
                    'priority' => 0.2
                );
            }
        }
        if ($urls) {
            return $urls;
        }
    }

    /**
     * @param $feature_id
     * @param $types
     * @return array
     */
    protected function getByTypes($feature_id, $types)
    {
        $product_features_model = new shopProductFeaturesModel();
        $sql = "SELECT DISTINCT pf.feature_value_id FROM ".$product_features_model->getTableName()." pf
                JOIN shop_product p ON pf.product_id = p.id
                WHERE pf.feature_id = i:0 AND pf.sku_id IS NULL AND p.status = 1";
        if ($types) {
            $sql .= " AND p.type_id IN (i:1)";
        }
        return $product_features_model->query($sql, $feature_id, $types)->fetchAll(null, true);
    }

    public function routing($route = array())
    {
        $url = $this->getSettings('url');
        if (!$url) {
            $url = 'brand';
        }
        $url_all = $this->getSettings('url_all');
        if (!$url_all) {
            $url_all = 'brands';
        }
        return array(
            $url_all.'/' => 'frontend/brands',
            $url.'/<brand>/' => 'frontend/brand'
        );
    }
}

