<?php

class shopFrontendCategoryAction extends shopFrontendAction
{
    /**
     * @var shopCategoryModel $model
     */
    protected $model;

    /**
     * @return shopCategoryModel
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = new shopCategoryModel();
        }
        return $this->model;
    }

    /**
     * @return mixed
     * @throws waException
     */
    protected function getCategory()
    {
        $category_model = $this->getModel();
        $url_field = waRequest::param('url_type') == 1 ? 'url' : 'full_url';

        if (waRequest::param('category_id')) {
            $category = $category_model->getById(waRequest::param('category_id'));
            if ($category) {
                $category_url = wa()->getRouteUrl('/frontend/category', array('category_url' => $category[$url_field]));
                if (urldecode(wa()->getConfig()->getRequestUrl(false, true)) !== $category_url) {
                    $q = waRequest::server('QUERY_STRING');
                    $this->redirect($category_url.($q ? '?'.$q : ''), 301);
                }
            }
        } else {
            $category = $category_model->getByField($url_field, waRequest::param('category_url'));
            if ($category && $category[$url_field] !== urldecode(waRequest::param('category_url'))) {
                $q = waRequest::server('QUERY_STRING');
                $this->redirect(wa()->getRouteUrl('/frontend/category', array('category_url' => $category[$url_field])).($q ? '?'.$q : ''), 301);
            }
        }
        $route = wa()->getRouting()->getDomain(null, true).'/'.wa()->getRouting()->getRoute('url');
        if ($category) {
            $category_routes_model = new shopCategoryRoutesModel();
            $routes = $category_routes_model->getRoutes($category['id']);
        }
        if (!$category || (!empty($routes) && !in_array($route, $routes))) {
            throw new waException('Category not found', 404);
        }
        $category['subcategories'] = $category_model->getSubcategories($category, $route);
        $category_url = wa()->getRouteUrl('shop/frontend/category', array('category_url' => '%CATEGORY_URL%'));
        foreach ($category['subcategories'] as &$sc) {
            $sc['url'] = str_replace('%CATEGORY_URL%', waRequest::param('url_type') == 1 ? $sc['url'] : $sc['full_url'], $category_url);
            $sc['params'] = array();
        }
        unset($sc);

        // params for category and subcategories
        $category['params'] = array();
        $category_params_model = new shopCategoryParamsModel();
        $rows = $category_params_model->getByField('category_id', array_keys(array($category['id'] => 1) + $category['subcategories']), true);
        foreach ($rows as $row) {
            if (!empty($category['subcategories'][$row['category_id']])) {
                $category['subcategories'][$row['category_id']]['params'][$row['name']] = $row['value'];
            } elseif ($row['category_id'] == $category['id']) {
                $category['params'][$row['name']] = $row['value'];
            }
        }

        // smarty description
        if ($this->getConfig()->getOption('can_use_smarty') && $category['description']) {
            $category['description'] = wa()->getView()->fetch('string:'.$category['description']);
        }

        // Open Graph data
        $category_og_model = new shopCategoryOgModel();
        $category['og'] = $category_og_model->get($category['id']) + array(
                'type'        => 'article',
                'title'       => $category['meta_title'],
                'description' => $category['meta_description'],
                'url'         => wa()->getConfig()->getHostUrl().wa()->getConfig()->getRequestUrl(false, true),
                'image'       => '',
            );

        return $category;
    }

    public function execute()
    {
        $category = $this->getCategory();
        $this->addCanonical();
        // breadcrumbs
        $root_category_id = $category['id'];
        if ($category['parent_id']) {
            $breadcrumbs = array();
            $path = array_reverse($this->getModel()->getPath($category['id']));
            $root_category = reset($path);
            $root_category_id = $root_category['id'];
            foreach ($path as $row) {
                $breadcrumbs[] = array(
                    'url'  => wa()->getRouteUrl('/frontend/category', array('category_url' => waRequest::param('url_type') == 1 ? $row['url'] : $row['full_url'])),
                    'name' => $row['name']
                );
            }
            if ($breadcrumbs) {
                $this->view->assign('breadcrumbs', $breadcrumbs);
            }
        }
        $this->view->assign('root_category_id', $root_category_id);
        // sort
        if ($category['type'] == shopCategoryModel::TYPE_DYNAMIC && !$category['sort_products']) {
            $category['sort_products'] = 'create_datetime DESC';
        }
        if ($category['sort_products'] && !waRequest::get('sort')) {
            $sort = explode(' ', $category['sort_products']);
            $this->view->assign('active_sort', $sort[0] == 'count' ? 'stock' : $sort[0]);
        } elseif (!$category['sort_products'] && !waRequest::get('sort')) {
            $this->view->assign('active_sort', '');
        }
        $this->view->assign('category', $category);

        // products
        $collection = new shopProductsCollection('category/'.$category['id']);

        $this->setCollection($collection, $category['params']['products_per_page']);

        $filter_data = waRequest::get();
        $filters = array();
        $feature_map = array();

        // filters
        if ($category['filter']) {
            $filter_ids = explode(',', $category['filter']);
            $feature_model = new shopFeatureModel();
            $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
            if ($features) {
                $features = $feature_model->getValues($features);
            }
            $category_value_ids = $collection->getFeatureValueIds(false);

            foreach ($filter_ids as $fid) {
                if ($fid == 'price') {
                    $range = $collection->getPriceRange();
                    if ($range['min'] != $range['max']) {
                        $filters['price'] = array(
                            'min' => shop_currency($range['min'], null, null, false),
                            'max' => shop_currency($range['max'], null, null, false),
                        );
                    }
                } elseif (isset($features[$fid]) && isset($category_value_ids[$fid])) {
                    $feature_map[$features[$fid]['code']] = $fid;
                    $filters[$fid] = $features[$fid];
                    $min = $max = $unit = null;
                    foreach ($filters[$fid]['values'] as $v_id => $v) {
                        if (!in_array($v_id, $category_value_ids[$fid])) {
                            unset($filters[$fid]['values'][$v_id]);
                        } else {
                            if ($v instanceof shopRangeValue) {
                                $begin = $this->getFeatureValue($v->begin);
                                if (is_numeric($begin) && ($min === null || (float)$begin < (float)$min)) {
                                    $min = $begin;
                                }
                                $end = $this->getFeatureValue($v->end);
                                if (is_numeric($end) && ($max === null || (float)$end > (float)$max)) {
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
        }

        $category_filters = $filters;

        if ($category['type'] == shopCategoryModel::TYPE_DYNAMIC) {

            // Collect feature codes we do not have IDs for
            $feature_codes_to_fix_ids = array();

            $conditions = shopProductsCollection::parseConditions($category['conditions']);
            foreach ($conditions as $field => $field_conditions) {
                switch ($field) {
                    case 'price':
                        foreach ($field_conditions as $condition) {
                            $type = reset($condition);
                            switch ($type) {
                                case '>=':
                                    $min = shop_currency(doubleval(end($condition)), null, null, false);

                                    if (empty($filter_data['price_min'])) {
                                        $filter_data['price_min'] = $min;
                                    } else {
                                        $filter_data['price_min'] = max($min, $filter_data['price_min']);
                                    }

                                    if (empty($category_filters['price'])) {
                                        $range = $collection->getPriceRange();
                                        if ($range['min'] != $range['max']) {
                                            $category_filters['price'] = array(
                                                'min' => shop_currency($range['min'], null, null, false),
                                                'max' => shop_currency($range['max'], null, null, false),
                                            );
                                        }
                                    } elseif (isset($filters['price']['min'])) {
                                        $filters['price']['min'] = max($filter_data['price_min'], $filters['price']['min']);
                                    }
                                    break;
                                case '<=':
                                    $max = shop_currency(doubleval(end($condition)), null, null, false);
                                    if (empty($filter_data['price_max'])) {
                                        $filter_data['price_max'] = $max;
                                    } else {
                                        $filter_data['price_max'] = min($max, $filter_data['price_max']);
                                    }

                                    if (empty($category_filters['price'])) {
                                        $range = $collection->getPriceRange();
                                        if ($range['min'] != $range['max']) {
                                            $category_filters['price'] = array(
                                                'min' => shop_currency($range['min'], null, null, false),
                                                'max' => shop_currency($range['max'], null, null, false),
                                            );
                                        }
                                    } elseif (isset($filters['price']['max'])) {
                                        $filters['price']['max'] = min($filter_data['price_max'], $filters['price']['max']);
                                    }
                                    break;

                            }
                        }

                        break;
                    case 'count':
                        /**
                         * count = {array} [2]
                         * 0 = ">="
                         * 1 = ""
                         */
                        break;
                    case 'rating':
                    case 'compare_price':
                    case 'tag':
                        break;
                    default:
                        if (preg_match('@(\w+)\.(value_id)$@', $field, $matches)) {
                            $feature_code = $matches[1];
                            $value_id = array_map('intval', preg_split('@[,\s]+@', end($field_conditions)));
                            if (!isset($feature_map[$feature_code])) {
                                // $feature_map is not guaranteed to contain all features at this point.
                                // We will fetch and fix them later.
                                $feature_codes_to_fix_ids[$feature_code] = $feature_code;
                            }
                            $feature_id = ifset($feature_map, $feature_code, $feature_code);
                            if (!isset($category_filters[$feature_id])) {
                                $category_filters[$feature_id] = array();
                            }
                            $category_filters[$feature_id] += array(
                                'code' => $feature_code,
                            );
                            if (!empty($filter_data[$feature_code])) {
                                //$filter_data[$feature_code] = array_intersect($filter_data[$feature_code], $value_id);
                            } else {
                                $filter_data[$feature_code] = $value_id;
                            }

                            if (!empty($filters[$feature_id]['values'])) {
                                foreach ($filters[$feature_id]['values'] as $_value_id => $_value) {
                                    if (!in_array($_value_id, $value_id)) {
                                        unset($filters[$feature_id]['values'][$_value_id]);
                                    }
                                }
                            }
                        }
                        break;
                }
            }

            // Loop above might have written filter code where filter_id is expected,
            // because it did not find feature_id by code. This fixes that.
            if ($feature_codes_to_fix_ids) {
                if (empty($feature_model)) {
                    $feature_model = new shopFeatureModel();
                }
                foreach($feature_model->getByCode($feature_codes_to_fix_ids) as $f) {
                    if (isset($category_filters[$f['code']])) {
                        $category_filters[$f['id']] = $category_filters[$f['code']];
                        unset($category_filters[$f['code']]);
                    }
                }
            }
        }

        if ($filters) {
            foreach ($filters as $field => $filter) {
                if (isset($filters[$field]['values']) && (!count($filters[$field]['values']))) {
                    unset($filters[$field]);
                }
            }
            $this->view->assign('filters', $filters);
        }

        // set meta
        wa()->getResponse()->setTitle($category['meta_title']);
        wa()->getResponse()->setMeta('keywords', $category['meta_keywords']);
        wa()->getResponse()->setMeta('description', $category['meta_description']);
        foreach (ifset($category['og'], array()) as $property => $content) {
            $content && wa()->getResponse()->setOGMeta('og:'.$property, $content);
        }

        /**
         * @event frontend_category
         * @return array[string]string $return[%plugin_id%] html output for category
         */
        $this->view->assign('frontend_category', wa()->event('frontend_category', $category));

        // default title and meta
        if (!wa()->getResponse()->getTitle()) {
            wa()->getResponse()->setTitle(shopCategoryModel::getDefaultMetaTitle($category));
        }

        if (!wa()->getResponse()->getMeta('keywords')) {
            wa()->getResponse()->setMeta('keywords', shopCategoryModel::getDefaultMetaKeywords($category));
        }

        $this->setThemeTemplate('category.html');
    }

    /**
     * @param shopDimensionValue|double $v
     * @return double
     */
    protected function getFeatureValue($v)
    {
        if ($v instanceof shopDimensionValue) {
            return $v->value_base_unit;
        }
        if (is_object($v)) {
            return $v->value;
        }
        return $v;
    }

    protected function sortSkus($a, $b)
    {
        if ($a['sort'] == $b['sort']) {
            return 0;
        }
        return ($a['sort'] < $b['sort']) ? -1 : 1;
    }

    const CATEGORY_CACHE_TTL = 900;
    const CATEGORY_CACHE_GROUP = 'shop/frontend_category';

    public function display($clear_assign = true)
    {
        $cached_html = $this->getCachedCategoryHtml();
        if ($cached_html !== null) {
            $this->applyBrowserNoCacheHeaders();
            wa()->getResponse()->addHeader('X-Shop-Cache', 'category-hit');
            return $this->patchCategoryHtmlHead($cached_html);
        }
        $this->applyBrowserNoCacheHeaders();
        wa()->getResponse()->addHeader('X-Shop-Cache', 'category-miss');

        $html = parent::display(false);
        $html = $this->patchCategoryHtmlHead($html);
        $this->setCachedCategoryHtml($html);

        return $html;
    }

    /**
     * Full-page cache хранит старый &lt;head&gt;; title/meta задаются в execute() + SEO-плагин.
     *
     * @param string $html
     * @return string
     */
    protected function patchCategoryHtmlHead($html)
    {
        $response = wa()->getResponse();
        $category = $this->view->getVars('category');

        if (!$response->getTitle() && !empty($category['id'])) {
            $response->setTitle(shopCategoryModel::getDefaultMetaTitle($category));
        }
        if (!$response->getMeta('keywords') && !empty($category['id'])) {
            $response->setMeta('keywords', shopCategoryModel::getDefaultMetaKeywords($category));
        }
        if (!$response->getMeta('description') && !empty($category['meta_description'])) {
            $response->setMeta('description', $category['meta_description']);
        }

        return $this->patchHtmlHeadFromResponse($html);
    }

    protected function canUseCategoryCache()
    {
        if (waSystemConfig::isDebug() || waRequest::isXMLHttpRequest()) {
            return false;
        }
        if ($this->isLocalDevHost()) {
            return false;
        }
        if (waRequest::get('preview') || wa()->getUser()->isAuth()) {
            return false;
        }
        if (waRequest::method() !== 'get' || waRequest::get()) {
            return false;
        }
        if (!waRequest::param('category_url') && !waRequest::param('category_id')) {
            return false;
        }

        return true;
    }

    protected function getCategoryCacheKey()
    {
        $category = $this->resolveCategoryForCache();
        if (!$category) {
            return null;
        }

        $mtime = !empty($category['edit_datetime'])
            ? $category['edit_datetime']
            : ifset($category, 'create_datetime', '0');
        $routing = wa()->getRouting();
        $route = $routing->getRoute();

        return md5(implode('|', array(
            $routing->getDomain(null, true),
            ifset($route, 'url', ''),
            waRequest::getTheme(),
            $category['id'],
            $mtime,
            'head-meta-v2',
        )));
    }

    /**
     * @return array|null
     */
    protected function resolveCategoryForCache()
    {
        $vars = $this->view->getVars();
        if (!empty($vars['category']['id'])) {
            return $vars['category'];
        }

        $category_model = $this->getModel();
        $url_field = waRequest::param('url_type') == 1 ? 'url' : 'full_url';

        if (waRequest::param('category_id')) {
            return $category_model->getById(waRequest::param('category_id'));
        }
        if (waRequest::param('category_url')) {
            $url = urldecode(waRequest::param('category_url'));
            return $category_model->getByField($url_field, $url);
        }

        return null;
    }

    protected function getCategoryCacheFilePath($key)
    {
        return wa()->getCachePath('cache/'.$key.'.php', self::CATEGORY_CACHE_GROUP);
    }

    /**
     * waSerializeCache::readFromFile требует is_writable — на prod после git pull
     * файлы часто root-owned и чтение молча не срабатывает.
     *
     * @param string $key
     * @return string|null
     */
    protected function readCategoryCacheValue($key)
    {
        $file = $this->getCategoryCacheFilePath($key);
        if (!file_exists($file) || !is_readable($file)) {
            return null;
        }

        $info = @unserialize(file_get_contents($file));
        if (!is_array($info) || !isset($info['value']) || !is_string($info['value']) || $info['value'] === '') {
            return null;
        }
        if (!empty($info['ttl']) && $info['ttl'] >= 0 && time() - $info['time'] >= $info['ttl']) {
            return null;
        }

        return $info['value'];
    }

    /**
     * @return string|null
     */
    protected function getCachedCategoryHtml()
    {
        if (!$this->canUseCategoryCache()) {
            return null;
        }

        $key = $this->getCategoryCacheKey();
        if ($key === null) {
            return null;
        }

        $cache = new waSerializeCache(
            $key,
            self::CATEGORY_CACHE_TTL,
            self::CATEGORY_CACHE_GROUP
        );
        $html = $cache->isCached() ? $cache->get() : null;
        if (!is_string($html) || $html === '') {
            $html = $this->readCategoryCacheValue($key);
        }

        if (!is_string($html) || $html === '') {
            return null;
        }

        // Битый кэш (пустой <title>) не отдаём — пусть перегенерируется.
        // Защита от застрявшего/root-owned файла кэша на prod.
        if (preg_match('/<title[^>]*>\s*<\/title>/i', $html)) {
            return null;
        }

        return $html;
    }

    protected function setCachedCategoryHtml($html)
    {
        if (!$this->canUseCategoryCache() || !is_string($html) || $html === '') {
            return;
        }

        $key = $this->getCategoryCacheKey();
        if ($key === null) {
            return;
        }

        $cache = new waSerializeCache(
            $key,
            self::CATEGORY_CACHE_TTL,
            self::CATEGORY_CACHE_GROUP
        );
        if ($cache->set($html)) {
            $file = $this->getCategoryCacheFilePath($key);
            if (file_exists($file)) {
                @chmod($file, 0664);
            }
        }
    }
}
