<?php

/**
 * Class shopFrontendAction
 * @method shopConfig getConfig()
 */
class shopFrontendAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new shopFrontendLayout());
        }
    }

    public function addCanonical()
    {
        $get_vars = waRequest::get();
        $ignore = array('page');
        foreach ($ignore as $k) {
            if (isset($get_vars[$k])) {
                unset($get_vars[$k]);
            }
        }
        if ($get_vars) {
            $this->view->assign('canonical', wa()->getConfig()->getHostUrl().wa()->getConfig()->getRequestUrl(false, true));
        }
    }

    public function getStoreName()
    {
        $title = waRequest::param('title');
        if (!$title) {
            $title = $this->getConfig()->getGeneralSettings('name');
        }
        if (!$title) {
            $app = wa()->getAppInfo();
            $title = $app['name'];
        }
        return htmlspecialchars($title);
    }

    protected function setCollection(shopProductsCollection $collection, $limit = null)
    {
        $collection->filters(waRequest::get());

        if(!$limit){
            $limit = (int)waRequest::cookie('products_per_page');
            if (!$limit || $limit < 0 || $limit > 500) {
                $limit = $this->getConfig()->getOption('products_per_page');
            }
        }

        $page = waRequest::get('page', 1, 'int');
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $collection->setOptions(array(
            'overwrite_product_prices' => true,
        ));
        $products = $collection->getProducts('*,skus_filtered,skus_image', $offset, $limit);

        $count = $collection->count();

        $pages_count = ceil((float)$count / $limit);
        $this->view->assign('pages_count', $pages_count);

        $this->view->assign('products', $products);
        $this->view->assign('products_count', $count);
    }

    public function execute()
    {
        if (wa()->getRouting()->getCurrentUrl()) {
            throw new waException('Page not found', 404);
        }
        $title = waRequest::param('title');
        if (!$title) {
            $app = wa()->getAppInfo();
            $title = $app['name'];
        }
        $this->getResponse()->setTitle($title);
        $this->getResponse()->setMeta('keywords', waRequest::param('meta_keywords'));
        $this->getResponse()->setMeta('description', waRequest::param('meta_description'));


        // Open Graph
        $og_url = null;
        foreach (array('title', 'image', 'video', 'description', 'type', 'url') as $k) {
            if (waRequest::param('og_'.$k)) {
                if (($k == 'url') && strlen(waRequest::param('og_'.$k))) {
                    $og_url = false;
                } elseif ($og_url === null) {
                    $og_url = true;
                }
                $this->getResponse()->setOGMeta('og:'.$k, waRequest::param('og_'.$k));
            }
        }
        if ($og_url) {
            $og_url = wa()->getConfig()->getHostUrl().wa()->getConfig()->getRequestUrl(false, true);
            $this->getResponse()->setOGMeta('og:url', $og_url);
        }

        /**
         * @event frontend_homepage
         * @return array[string]string $return[%plugin_id%] html output for head section
         */
        $this->view->assign('frontend_homepage', wa()->event('frontend_homepage'));

        $this->setThemeTemplate('home.html');

    }

    const HOME_CACHE_TTL = 900;
    const HOME_CACHE_GROUP = 'shop/frontend_home';

    public function display($clear_assign = true)
    {
        /**
         * @event frontend_nav
         * @return array[string]string $return[%plugin_id%] html output for navigation section
         */
        $this->view->assign('frontend_nav', wa()->event('frontend_nav'));

        /**
         * @event frontend_nav_aux
         * @return array[string]string $return[%plugin_id%] html output for navigation section
         */
        $this->view->assign('frontend_nav_aux', wa()->event('frontend_nav_aux'));

        // set globals
        $params = waRequest::param();
        foreach ($params as $k => $v) {
            if (in_array($k, array('url', 'module', 'action', 'meta_keywords', 'meta_description', 'private',
                'url_type', 'type_id', 'payment_id', 'shipping_id', 'currency', 'stock_id', 'public_stocks'))) {
                unset($params[$k]);
            }
        }
        $this->view->getHelper()->globals($params);

        $cached_html = $this->getCachedHomepageHtml();
        if ($cached_html !== null) {
            wa()->getResponse()->addHeader('X-Shop-Cache', 'home-hit');
            return $cached_html;
        }
        wa()->getResponse()->addHeader('X-Shop-Cache', 'home-miss');

        try {
            $html = parent::display(false);
        } catch (waException $e) {
            if ($e->getCode() == 404) {
                $url = $this->getConfig()->getRequestUrl(false, true);
                if (substr($url, -1) !== '/' && strpos(substr($url, -5), '.') === false) {
                    wa()->getResponse()->redirect($url.'/', 301);
                }
            }
            /**
             * @event frontend_error
             */
            wa()->event('frontend_error', $e);
            $this->view->assign('error_message', $e->getMessage());
            $code = $e->getCode();
            $this->view->assign('error_code', $code);
            $this->getResponse()->setStatus($code ? $code : 500);
            $this->setThemeTemplate('error.html');
            $html = $this->view->fetch($this->getTemplate());
        }

        $this->setCachedHomepageHtml($html);

        return $html;
    }

    protected function canUseHomepageCache()
    {
        if (waSystemConfig::isDebug() || waRequest::isXMLHttpRequest()) {
            return false;
        }
        if (waRequest::get('preview') || wa()->getUser()->isAuth()) {
            return false;
        }
        if (waRequest::method() !== 'get' || waRequest::get()) {
            return false;
        }
        if (wa()->getRouting()->getCurrentUrl()) {
            return false;
        }
        $vars = $this->view->getVars();
        if (!empty($vars['error_code'])) {
            return false;
        }

        return true;
    }

    protected function getHomepageCacheKey()
    {
        $routing = wa()->getRouting();
        $route = $routing->getRoute();

        return md5(implode('|', array(
            $routing->getDomain(null, true),
            ifset($route, 'url', ''),
            waRequest::getTheme(),
            date('Y-m-d'),
        )));
    }

    protected function getHomeCacheFilePath($key)
    {
        return wa()->getCachePath('cache/'.$key.'.php', self::HOME_CACHE_GROUP);
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected function readHomeCacheValue($key)
    {
        $file = $this->getHomeCacheFilePath($key);
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
    protected function getCachedHomepageHtml()
    {
        if (!$this->canUseHomepageCache()) {
            return null;
        }

        $key = $this->getHomepageCacheKey();
        $cache = new waSerializeCache($key, self::HOME_CACHE_TTL, self::HOME_CACHE_GROUP);
        $html = $cache->isCached() ? $cache->get() : null;
        if (!is_string($html) || $html === '') {
            $html = $this->readHomeCacheValue($key);
        }

        return is_string($html) && $html !== '' ? $html : null;
    }

    protected function setCachedHomepageHtml($html)
    {
        if (!$this->canUseHomepageCache() || !is_string($html) || $html === '') {
            return;
        }

        $key = $this->getHomepageCacheKey();
        $cache = new waSerializeCache($key, self::HOME_CACHE_TTL, self::HOME_CACHE_GROUP);
        if ($cache->set($html)) {
            $file = $this->getHomeCacheFilePath($key);
            if (file_exists($file)) {
                @chmod($file, 0664);
            }
        }
    }
}
