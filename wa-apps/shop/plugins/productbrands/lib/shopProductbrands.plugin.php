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
     * @var array
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
            $feature = self::getFeature();
            $products_settings = wa('shop')->getPlugin('productbrands')->getSettings('products');
            if ($feature) {
                $feature_model = new shopFeatureModel();
                $brands = $feature_model->getFeatureValues($feature);
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
                    } else {
                        $counts = array();
                    }
                } else {
                    $sql = "SELECT feature_value_id, COUNT(*) FROM " . $product_features_model->getTableName() . " pf
                        JOIN shop_product p ON pf.product_id = p.id
                        WHERE pf.feature_id = i:0 AND pf.sku_id IS NULL " . (wa()->getEnv() == 'frontend' && $products_settings == 'published' ? "AND p.status = 1 " : '') .
                        ($types ? 'AND p.type_id IN (i:1) ' : '') .
                        "GROUP BY pf.feature_value_id";
                    $counts = $product_features_model->query($sql, $feature['id'], $types)->fetchAll('feature_value_id', true);
                }
            } else {
                $brands = array();
                $counts = array();
            }

            if ($brands) {
                $brands_model = new shopProductbrandsModel();
                $rows = $brands_model->getById(array_keys($brands));
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
                foreach ($brands as $id => $name) {
                    if (wa()->getEnv() == 'frontend' && $products_settings !== 'all' && !isset($counts[$id])) {
                        unset($brands[$id]);
                        continue;
                    }
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
                        if ($brands[$id]['hidden']) {
                            unset($brands[$id]);
                            continue;
                        }
                        $brand_url = $brands[$id]['url'] ? $brands[$id]['url'] : urlencode($name);
                        $brands[$id]['url'] = str_replace('%BRAND%', $brand_url, $url);
                    }
                    $brands[$id]['count'] = isset($counts[$id]) ? $counts[$id] : 0;
                }
                if (wa()->appExists('mylang') && wa()->getEnv() == 'frontend') {
                    $mylang_params = ['appId' => 'shop', 'type' => 'feature_value', 'ids' => array_keys($brands), 'format' => true];
                    $mylang_translates = wa()->event(array('mylang', 'external_plugin_getdata'), $mylang_params);
                    if ($mylang_translates && !empty($mylang_translates['mylang'])) {
                        foreach ($mylang_translates['mylang'] as $t_id => $t) {
                            if (!empty($brands[$t_id]) && !empty($t[$feature['type']])) {
                                $brands[$t_id]['name'] = $t[$feature['type']];
                            }
                        }
                    }
                }
            }
            if ($brands && wa()->getSetting('sort', null, array('shop', 'productbrands'))) {
                uasort($brands, array('shopProductbrandsPlugin', 'sortBrands'));
            }
            self::$brands = $brands;
        }
        return self::$brands;
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

