<?php

/**
 * @author wa-apps.ru <info@wa-apps.ru>
 * @copyright 2013-2016 wa-apps.ru
 * @license Webasyst License http://www.webasyst.ru/terms/#eula
 * @link http://www.webasyst.ru/store/plugin/shop/productbrands/
 */
class shopProductbrandsPluginFrontendBrandAction extends shopFrontendCategoryAction
{

    /**
     * @param $brand
     * @param shopProductsCollection $collection
     * @return array
     */
    protected function getFilters($brand, $collection)
    {
        $filters = array();
        if ($brand['filter']) {
            $filter_ids = explode(',', $brand['filter']);
            $feature_model = new shopFeatureModel();
            $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
            if ($features) {
                $features = $feature_model->getValues($features);
            }
            $brand_value_ids = $collection->getFeatureValueIds();

            foreach ($filter_ids as $fid) {
                if ($fid == 'price') {
                    $range = $collection->getPriceRange();
                    if ($range['min'] != $range['max']) {
                        $filters['price'] = array(
                            'min' => shop_currency($range['min'], null, null, false),
                            'max' => shop_currency($range['max'], null, null, false),
                        );
                        $this->view->assign('price_min', $filters['price']['min']);
                        $this->view->assign('price_max', $filters['price']['max']);
                    }
                } elseif (isset($features[$fid]) && isset($brand_value_ids[$fid])) {
                    $filters[$fid] = $features[$fid];
                    $min = $max = $unit = null;
                    foreach ($filters[$fid]['values'] as $v_id => $v) {
                        if (!in_array($v_id, $brand_value_ids[$fid])) {
                            unset($filters[$fid]['values'][$v_id]);
                        } else {
                            if ($v instanceof shopRangeValue) {
                                $begin = $this->getFeatureValue($v->begin);
                                if ($min === null || $begin < $min) {
                                    $min = $begin;
                                }
                                $end = $this->getFeatureValue($v->end);
                                if ($max === null || $end > $max) {
                                    $max = $end;
                                    if ($v->end instanceof shopDimensionValue) {
                                        $unit = $v->end->unit;
                                    }
                                }
                            } else {
                                $tmp_v = $this->getFeatureValue($v);
                                if ($min === null || $tmp_v < $min) {
                                    $min = $tmp_v;
                                }
                                if ($max === null || $tmp_v > $max) {
                                    $max = $tmp_v;
                                    if ($v instanceof shopDimensionValue) {
                                        $unit = $v->unit;
                                    }
                                }
                            }
                        }
                    }
                    if (!$filters[$fid]['selectable'] && ($filters[$fid]['type'] == 'double' ||
                            substr($filters[$fid]['type'], 0, 6) == 'range.' ||
                            substr($filters[$fid]['type'], 0, 10) == 'dimension.')
                    ) {
                        if ($min == $max) {
                            unset($filters[$fid]);
                        } else {
                            $type = preg_replace('/^[^\.]*\./', '', $filters[$fid]['type']);
                            if ($type != 'double') {
                                $filters[$fid]['base_unit'] = shopDimension::getBaseUnit($type);
                                $filters[$fid]['unit'] = shopDimension::getUnit($type, $unit);
                                if ($filters[$fid]['base_unit']['value'] != $filters[$fid]['unit']['value']) {
                                    $dimension = shopDimension::getInstance();
                                    $min = $dimension->convert($min, $type, $filters[$fid]['unit']['value']);
                                    $max = $dimension->convert($max, $type, $filters[$fid]['unit']['value']);
                                }
                            }
                            $filters[$fid]['min'] = $min;
                            $filters[$fid]['max'] = $max;
                        }
                    }
                }
            }

            if ($filters && class_exists('shopFiltersDescriptionsModel')) {
                $desc_model = new shopFiltersDescriptionsModel();
                $desc_ids = $desc_model->getFeatureIds(array_keys($filters));
                foreach ($desc_ids as $f_id) {
                    $filters[$f_id]['description'] = true;
                }
            }

            $this->mylangFilters($filters);

            $this->view->assign('filters', $filters);
            $this->view->assign('filters_hash', 'brand/'.$brand['id']);
        }

        return $filters;
    }

    protected function mylangFilters(&$filters)
    {
        if (wa()->appExists('mylang') && class_exists('mylangHelper') && mylangHelper::checkSite()) {
            $v_ids = [];
            foreach ($filters as $k => $f) {
                $f_ids[] = ifset($f['id']);
                if (!(isset($f['type']) && ($f['type'] == 'varchar' || $f['type'] == 'text' || $f['type'] == 'color'))) {
                    continue;
                }
                if (!empty($f['values']) && is_array($f['values'])) {
                    $v_ids[$f['type']] = array_merge(array_keys($f['values']), ifset($v_ids[$f['type']], []));
                }
            }
            $f_ids = array_filter($f_ids);
            if (empty($f_ids)) {
                return;
            }

            $model = new mylangModel();
            $names = $model->getValues('feature', $f_ids, mylangLocale::currentLocale());
            foreach ($names as $k => $f) {
                $filters[$k]['name'] = $f['text'];
            }
            $keys = array_keys($v_ids);
            if (empty($keys)) {
                return $filters;
            }
            $values = $model->getPlainValuesSubtype('feature_value', $keys, $v_ids, mylangLocale::currentLocale());
            if (empty($values)) {
                return $filters;
            }
            foreach ($filters as $key => $f) {
                if (!isset($f['values'])) {
                    continue;
                }
                foreach ($f['values'] as $k => $v) {
                    if (isset($values[$f['type']][$k])) {
                        if ($f['type'] == 'color') {
                            $filters[$key]['values'][$k]->__set('value', $values[$f['type']][$k]);
                        } else {
                            $filters[$key]['values'][$k] = $values[$f['type']][$k];
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $feature
     * @param $brand_url
     * @return array
     * @throws waException
     */
    protected function getBrand($feature, $brand_url)
    {
        $feature_model = new shopFeatureModel();
        $values_model = $feature_model->getValuesModel($feature['type']);

        $brands_model = new shopProductbrandsModel();
        $brand = $brands_model->getByField('url', $brand_url);

        if (!$brand) {
            $sql = "SELECT * FROM ".$values_model->getTableName()." 
            WHERE `feature_id` = ".(int)$feature['id']." 
            AND `value` LIKE '".$values_model->escape($brand_url, 'like')."'";
            $value_row = $values_model->query($sql)->fetchAssoc();
            if (!$value_row) {
                throw new waException(_wp('Brand not found'), 404);
            }
            $value_id = $value_row['id'];
            $brand = $brands_model->getBrand($value_id);
        } else {
            $brand['params'] = shopProductbrandsModel::getParams($brand['params']);
            // check feature value exists
            if (!$values_model->getById($brand['id'])) {
                $brands_model->updateById($brand['id'], array('url' => ''));
                throw new waException(_wp('Brand not found'), 404);
            }
        }

        $b_url = !empty($brand['url']) ? $brand['url'] : $brand['name'];
        if ($b_url != urldecode($brand_url)) {
            $url = wa()->getRouteUrl('/frontend/brand', array('brand' => $b_url));
            if ($q = waRequest::server('QUERY_STRING')) {
                $url .= '?'.$q;
            }
            $this->redirect($url, 301);
        }

        if (wa()->appExists('mylang')) {
            $mylang_params = ['appId' => 'shop', 'type' => 'feature_value', 'ids' => $brand['id'], 'format' => true];
            $mylang_translates = wa()->event(array('mylang', 'external_plugin_getdata'), $mylang_params);
            if ($mylang_translates && !empty($mylang_translates['mylang'])) {
                foreach ($mylang_translates['mylang'] as $t_id => $t) {
                    if ($brand['id'] == $t_id && !empty($t[$feature['type']])) {
                        $brand['name'] = $t[$feature['type']];
                    }
                }
            }
        }

        return $brand;
    }

    /**
     * @param $brand
     * @param $feature
     * @return array
     */
    protected function getCategories($brand, $feature)
    {
        $c_setting = wa()->getSetting('categories_filter', '', array('shop', 'productbrands'));
        // Не показывать категории
        if ($c_setting == -1) {
            return array();
        }

        $category_model = new shopCategoryModel();

        $b_url = !empty($brand['url']) ? $brand['url'] : $brand['name'];

        // get categories
        $sql = "SELECT cp.category_id, COUNT(DISTINCT cp.product_id) FROM shop_category_products cp
                JOIN shop_product_features pf ON cp.product_id = pf.product_id
                WHERE pf.feature_id = ".(int)$feature['id']." AND pf.feature_value_id = ".(int)$brand['id']."
                GROUP BY cp.category_id";
        $categories_count = $category_model->query($sql)->fetchAll('category_id', true);

        if ($categories_count) {
            $route = wa()->getRouting()->getDomain(null, true) . '/' . wa()->getRouting()->getRoute('url');
            $sql = 'SELECT * FROM shop_category c
                    LEFT JOIN shop_category_routes cr ON c.id = cr.category_id
                    WHERE c.id IN (i:ids) AND c.status = 1 AND
                    (cr.route IS NULL OR cr.route = s:route)
                    ORDER BY c.left_key';
            $categories = $category_model->query($sql, array('ids' => array_keys($categories_count),
                'route' => $route))->fetchAll('id', true);
            foreach ($categories as $c_id => $c) {
                $categories[$c_id]['count'] = $categories_count[$c_id];
            }
        } else {
            $categories = array();
        }
        if (!$c_setting) {
            $category_url = wa()->getRouteUrl('shop/frontend/category', array('category_url' => '%CATEGORY_URL%')) . '?' . $feature['code'] . '=' . $brand['id'];
        } elseif ($c_setting == 1) {
            $category_url = wa()->getRouteUrl('shop/frontend/brand', array('brand' => urlencode($b_url) . '/%CATEGORY_URL%'));
        } else {
            $category_url = wa()->getRouteUrl('shop/frontend/category', array('category_url' => '%CATEGORY_URL%'));
        }
        foreach ($categories as &$c) {
            $c['url'] = str_replace('%CATEGORY_URL%', waRequest::param('url_type') == 1 ? $c['url'] : $c['full_url'], $category_url);
        }
        unset($c);

        if (wa()->appExists('mylang') && $categories) {
            $mylang_params = ['appId' => 'shop', 'type' => 'category', 'ids' => array_keys($categories), 'format' => true];
            $mylang_translates = wa()->event(array('mylang', 'external_plugin_getdata'), $mylang_params);
            if ($mylang_translates && !empty($mylang_translates['mylang'])) {
                foreach ($mylang_translates['mylang'] as $c_id => $c) {
                    if ($categories[$c_id]) {
                        $categories[$c_id]['name'] = $c['name'];
                    }
                }
            }
        }

        return $categories;
    }

    public function setCanonical()
    {
        $brand = waRequest::param('brand');

        // If brand has spaces ..
        $brand_has_spaces = preg_match("/\s+/", $brand);
        if ($brand_has_spaces) {

            $request_uri = wa()->getConfig()->getRequestUrl(false, true);

            // and request uri has %20
            // than mark canonical url with +, because plugin use urlencode everywhere when encode urls (urlencode convert spaces to '+'s)
            if (strpos($request_uri, '%20') !== false) {
                $request_uri = str_replace('%20', '+', $request_uri);
                $this->getResponse()->setCanonical(wa()->getConfig()->getHostUrl() . $request_uri);

                // no need call parent method, cause parent doesn't know about spaces encoding
                return;
            }
        }

        $this->getResponse()->setCanonical(wa()->getConfig()->getRootUrl(true) . wa()->getConfig()->getRequestUrl(true, true));
    }

    public function execute()
    {
        $key = array('shop', 'productbrands');
        $brand_url = waRequest::param('brand');

        $this->setCanonical();

        $category_model = new shopCategoryModel();
        if (strpos($brand_url, '/')) {
            list($brand_url, $category_url) = explode('/', $brand_url, 2);
            $category = $category_model->getByField(waRequest::param('url_type') == 1 ? 'url' : 'full_url', $category_url);
            if (!$category) {
                throw new waException('Category not found', 404);
            }
            $this->view->assign('category', $category);
        }

        $feature_model = new shopFeatureModel();
        $feature_id = wa()->getSetting('feature_id', '', $key);
        $feature = $feature_model->getById($feature_id);

        $brand = $this->getBrand($feature, $brand_url);
        $b_url = !empty($brand['url']) ? $brand['url'] : $brand['name'];

        $categories = $this->getCategories($brand, $feature);
        $this->view->assign('categories', $categories);

        if (!empty($category)) {
            $c = new shopProductbrandsPluginCollection('category/'.$category['id']);

            $frontend_url = wa()->getRouteUrl('shop/frontend/brand', array('brand' => urlencode($b_url)));
            $breadcrumbs = array(
                array(
                    'url' => $frontend_url,
                    'name' => $brand['name'],
                )
            );
            $this->view->assign('breadcrumbs', $breadcrumbs);
            if ($this->layout) {
                $this->layout->assign('breadcrumbs', $breadcrumbs);
            }
            $this->view->assign('canonical', $frontend_url);
        } else {
            if (!empty($brand['params']['canonical'])) {
                $this->view->assign('canonical', $brand['params']['canonical']);
            }
            $c = new shopProductbrandsPluginCollection();
        }

        $c->addJoin('shop_product_features',
            'p.id = :table.product_id AND :table.feature_id = '.(int)$feature['id'],
            ':table.feature_value_id = '.(int)$brand['id']);

        // sorting
        $this->view->assign('sorting', $brand['enable_sorting']);
        if ($brand['sort_products'] && !waRequest::get('sort')) {
            $sort = explode(' ', $brand['sort_products']);
            $this->view->assign('active_sort', $sort[0] == 'count' ? 'stock' : $sort[0]);
            $c->setBrandSortProducts($brand['sort_products']);
        } elseif (!$brand['sort_products'] && !waRequest::get('sort')) {
            $this->view->assign('active_sort', '');
        }

        $filters = $this->getFilters($brand, $c);

        $c->filters(waRequest::get(), true);
        $this->setCollection($c);

        if (!$c->count()) {
            $brands = shopProductbrandsPlugin::getBrands();
            if (empty($brands[$brand['id']])) {
                throw new waException(_wp('Brand not found'), 404);
            }
        }

        if ($brand['filter'] && !empty($filters)) {
            $this->fixPrices($filters);
        }

        if ($this->getConfig()->getOption('can_use_smarty') && $brand['description']) {
            $brand['description'] = wa()->getView()->fetch('string:'.$brand['description']);
        }

        $this->view->assign('brand', $brand);

        $h1 = $brand['h1'] ? $brand['h1'] : $brand['name'];
        if (wa()->getSetting('title_h1', '', $key) && $brand['title'] && empty($brand['h1'])) {
            $h1 = $brand['title'];
        }
        $this->view->assign(
            'title',
            htmlspecialchars(!empty($category) ? $category['name'] : $h1)
        );


        $title = $brand['title'] ? $brand['title'] : $brand['name'];
        if (!empty($category)) {
            $title .= ' - '.$category['name'];
            waRequest::setParam('brand', $title);
        }
        $this->getResponse()->setTitle($title);
        $og = array(
            'type' => 'website',
            'title' => $title,
            'url' => wa()->getConfig()->getHostUrl().wa()->getConfig()->getRequestUrl(false, true)
        );
        if ($brand['meta_keywords']) {
            $this->getResponse()->setMeta('keywords', $brand['meta_keywords']);
        }
        if ($brand['meta_description']) {
            $og['description'] = $brand['meta_description'];
            $this->getResponse()->setMeta('description', $brand['meta_description']);
        }
        if (!empty($brand['image'])) {
            $og['image'] = wa()->getDataUrl('brands/'.$brand['id'].'/'.$brand['id'].$brand['image'], true, 'shop', true);
        }

        if ($t_search = wa()->getSetting('template_search', '', $key)) {
            $html = $this->view->fetch('string:'.$t_search);
        } else {
            $html = $this->view->fetch(wa()->getAppPath('plugins/productbrands/templates/', 'shop').'frontendSearch.html');
        }

        if (!empty($og)) {
            $response = wa()->getResponse();
            if (method_exists($response, 'setOGMeta')) {
                foreach ($og as $property => $value) {
                    $response->setOGMeta('og:' . $property, $value);
                }
            }
        }

        /**
         * @var shopProductbrandsPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin('productbrands');

        /**
         * @event frontend_search
         * @return array[string]string $return[%plugin_id%] html output for search
         */
        $frontend_search = (array)wa()->event('frontend_search');
        if (!($template = $plugin->getSettings('brand_theme_template')) || !$this->setThemeTemplate($template)) {
            $this->view->assign('frontend_search', array('productbrands' => $html) + $frontend_search);
            $this->setThemeTemplate('search.html');
        }

        waSystem::popActivePlugin();
    }


    protected function fixPrices($filters)
    {
        // fix prices
        $products = $this->view->getVars('products');
        $product_ids = array();
        foreach ($products as $p_id => $p) {
            if ($p['sku_count'] > 1) {
                $product_ids[] = $p_id;
            }
        }
        if ($product_ids) {
            $min_price = $max_price = null;
            $tmp = array();
            foreach ($filters as $fid => $f) {
                if ($fid == 'price') {
                    $min_price = waRequest::get('price_min');
                    if (!empty($min_price)) {
                        $min_price = (double)$min_price;
                    } else {
                        $min_price = null;
                    }
                    $max_price = waRequest::get('price_max');
                    if (!empty($max_price)) {
                        $max_price = (double)$max_price;
                    } else {
                        $max_price = null;
                    }
                } else {
                    $fvalues = waRequest::get($f['code']);
                    if ($fvalues && !isset($fvalues['min']) && !isset($fvalues['max'])) {
                        $tmp[$fid] = $fvalues;
                    }
                }
            }

            $rows = array();
            if ($tmp) {
                $pf_model = new shopProductFeaturesModel();
                $rows = $pf_model->getSkusByFeatures($product_ids, $tmp);
            } elseif ($min_price || $max_price) {
                $ps_model = new shopProductSkusModel();
                $rows = $ps_model->getByField('product_id', $product_ids, true);
            }
            $product_skus = array();
            shopRounding::roundSkus($rows, $products);
            foreach ($rows as $row) {
                $product_skus[$row['product_id']][] = $row;
            }

            $default_currency = $this->getConfig()->getCurrency(true);
            if ($product_skus) {
                foreach ($product_skus as $product_id => $skus) {
                    $currency = $products[$product_id]['currency'];
                    usort($skus, array($this, 'sortSkus'));
                    $k = 0;
                    if ($min_price || $max_price) {
                        foreach ($skus as $i => $sku) {
                            if ($min_price) {
                                $tmp_price = shop_currency($min_price, true, $currency, false);
                                if ($sku['price'] < $tmp_price) {
                                    continue;
                                }
                            }
                            if ($max_price) {
                                $tmp_price = shop_currency($max_price, true, $currency, false);
                                if ($sku['price'] > $tmp_price) {
                                    continue;
                                }
                            }
                            $k = $i;
                            break;
                        }
                    }
                    $sku = $skus[$k];
                    if ($products[$product_id]['sku_id'] != $sku['id']) {
                        $products[$product_id]['sku_id'] = $sku['id'];
                        $products[$product_id]['frontend_url'] .= '?sku='.$sku['id'];
                        $products[$product_id]['price'] =
                            shop_currency($sku['price'], $currency, $default_currency, false);
                        $products[$product_id]['compare_price'] =
                            shop_currency($sku['compare_price'], $currency, $default_currency, false);
                    }
                }
                $this->view->assign('products', $products);
            }
        }
    }
}
