<?php

class shopFrontendProductPageAction extends shopFrontendProductAction
{
    public function execute()
    {
        $product_model = new shopProductModel();
        $product = $product_model->getByField('url', waRequest::param('product_url'));
        if (!$product) {
            throw new waException('Product not found', 404);
        }

        if ($types = waRequest::param('type_id')) {
            if (!in_array($product['type_id'], (array)$types)) {
                throw new waException(_w('Product not found'), 404);
            }
        }

        $product = new shopProduct($product, true);
        $this->view->assign('product', $product);

        $this->getBreadcrumbs($product, true);

        $page_model = new shopProductPagesModel();
        $page = $page_model->getByField(array('product_id' => $product['id'], 'url' => waRequest::param('page_url')));
        if (!$page || empty($page['status'])) {
            $hash = $this->appSettings('preview_hash');
            if (!$hash || md5($hash) != waRequest::get('preview')) {
                throw new waException('Page not found', 404);
            }
        }

        if (!$page) {
            throw new waException('Page not found', 404);
        }
        if (!$page['title']) {
            $page['title'] = $page['name'];
        }

        $page['content'] = $this->renderPageContent((string) $page['content'], $product['id'], $page['url']);

        $this->view->assign('page', $page);
        $this->view->assign('reviews_total_count', $this->getReviewsTotalCount($product['id']));

        $this->getResponse()->setTitle($product['name'].' - '.$page['title']);
        $this->getResponse()->setMeta(array(
            'keywords' => isset($page['keywords']) ? $page['keywords'] : '',
            'description' => isset($page['description']) ? $page['description'] : ''
        ));

        /**
         * @event frontend_product
         * @param shopProduct $product
         * @return array[string][string]string $return[%plugin_id%]['menu'] html output
         * @return array[string][string]string $return[%plugin_id%]['cart'] html output
         * @return array[string][string]string $return[%plugin_id%]['block_aux'] html output
         * @return array[string][string]string $return[%plugin_id%]['block'] html output
         */
        $this->view->assign('frontend_product', wa()->event('frontend_product', $product, array('menu','cart','block_aux','block')));

        $this->setThemeTemplate('product.page.html');
    }

    /**
     * Product subpages may contain Smarty ({$wa->…}, {if}).
     * Word/Office paste also contains {…} in data-ccp attributes; those are not Smarty
     * and make the compiler throw "Unexpected &".
     */
    protected function renderPageContent($raw, $product_id, $page_url)
    {
        $prepared = preg_replace_callback(
            '/\{([^{}]*)\}/',
            array($this, 'escapeNonSmartyBraces'),
            $raw
        );
        try {
            return $this->view->fetch('string:'.$prepared);
        } catch (Exception $e) {
            waLog::log(
                'product page Smarty failed product_id='.$product_id
                .' page='.$page_url
                .': '.$e->getMessage(),
                'shop/product-page-smarty.log'
            );
            return $raw;
        }
    }

    protected function escapeNonSmartyBraces($matches)
    {
        $inner = $matches[1];
        if (preg_match('/^\s*(?:\$|\/?\*|\/?(?:if|else|elseif|foreach|foreachelse|section|include|literal|assign|capture|strip|function|ldelim|rdelim)\b|\/?(?:shop|wa)[A-Za-z0-9_]*\s*::)/', $inner)) {
            return $matches[0];
        }
        return '&#123;'.$inner.'&#125;';
    }
}