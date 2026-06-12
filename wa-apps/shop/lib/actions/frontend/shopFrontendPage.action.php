<?php

class shopFrontendPageAction extends waPageAction
{
    public function __construct($params = null)
    {
        if (!class_exists('shopFrontendHeadMetaPatch', false)) {
            waAutoload::getInstance()->add(array(
                'shopFrontendHeadMetaPatch' => 'wa-apps/shop/lib/classes/shopFrontendHeadMetaPatch.class.php',
            ));
        }

        parent::__construct($params);
    }

    const PAGE_CACHE_TTL = 3600;
    const PAGE_CACHE_GROUP = 'shop/frontend_page';

    public function execute()
    {
        $this->setLayout(new shopFrontendLayout());
        parent::execute();
    }

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

        $cached_html = $this->getCachedPageHtml();
        if ($cached_html !== null) {
            $this->applyBrowserNoCacheHeaders();
            wa()->getResponse()->addHeader('X-Shop-Cache', 'page-hit');
            return $this->patchPageHtmlHead($cached_html);
        }

        try {
            $html = parent::display(false);
        } catch (waException $e) {
            if ($e->getCode() == 404) {
                $url = $this->getConfig()->getRequestUrl(false, true);
                if (substr($url, -1) !== '/' && substr($url, -9) !== 'index.php') {
                    $this->redirect($url.'/', 301);
                }
            }
            wa()->event('frontend_error', $e);
            $this->view->assign('error_message', $e->getMessage());
            $code = $e->getCode();
            $this->view->assign('error_code', $code);
            $this->getResponse()->setStatus($code ? $code : 500);
            $this->setThemeTemplate('error.html');
            $html = $this->view->fetch($this->getTemplate());
        }

        $this->applyBrowserNoCacheHeaders();
        wa()->getResponse()->addHeader('X-Shop-Cache', 'page-miss');
        $html = $this->patchPageHtmlHead($html);
        $this->setCachedPageHtml($html);

        return $html;
    }

    protected function patchPageHtmlHead($html)
    {
        $response = wa()->getResponse();
        $page = $this->view->getVars('page');

        if (!$response->getTitle() && !empty($page['title'])) {
            $response->setTitle($page['title']);
        }
        if (!$response->getMeta('keywords') && !empty($page['keywords'])) {
            $response->setMeta('keywords', $page['keywords']);
        }
        if (!$response->getMeta('description') && !empty($page['description'])) {
            $response->setMeta('description', $page['description']);
        }

        $canonical = $this->view->getVars('canonical');

        return shopFrontendHeadMetaPatch::apply($html, $canonical ? (string) $canonical : null);
    }

    protected function applyBrowserNoCacheHeaders()
    {
        $response = wa()->getResponse();
        $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->addHeader('Pragma', 'no-cache');
        $response->addHeader('Expires', '0');
    }

    protected function canUsePageCache()
    {
        if (waSystemConfig::isDebug() || waRequest::isXMLHttpRequest()) {
            return false;
        }
        $host = waRequest::server('HTTP_HOST');
        if ($host && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host)) {
            return false;
        }
        if (waRequest::get('preview') || wa()->getUser()->isAuth()) {
            return false;
        }
        if (waRequest::method() !== 'get' || waRequest::get()) {
            return false;
        }
        if (!waRequest::param('page_id')) {
            return false;
        }
        $vars = $this->view->getVars();
        if (!empty($vars['error_code'])) {
            return false;
        }

        return true;
    }

    protected function getPageCacheKey()
    {
        $vars = $this->view->getVars();
        $page = ifset($vars, 'page', array());
        $mtime = !empty($page['update_datetime']) ? $page['update_datetime'] : '0';
        $routing = wa()->getRouting();
        $route = $routing->getRoute();

        return md5(implode('|', array(
            $routing->getDomain(null, true),
            ifset($route, 'url', ''),
            waRequest::getTheme(),
            waRequest::param('page_id'),
            $mtime,
            'head-meta-v2',
        )));
    }

    /**
     * @return string|null null — кэш не используется; string — HTML
     */
    protected function getCachedPageHtml()
    {
        if (!$this->canUsePageCache()) {
            return null;
        }

        $cache = new waSerializeCache($this->getPageCacheKey(), self::PAGE_CACHE_TTL, self::PAGE_CACHE_GROUP);
        if (!$cache->isCached()) {
            return null;
        }

        $html = $cache->get();
        return is_string($html) && $html !== '' ? $html : null;
    }

    protected function setCachedPageHtml($html)
    {
        if (!$this->canUsePageCache() || !is_string($html) || $html === '') {
            return;
        }

        $cache = new waSerializeCache($this->getPageCacheKey(), self::PAGE_CACHE_TTL, self::PAGE_CACHE_GROUP);
        $cache->set($html);
    }
}
