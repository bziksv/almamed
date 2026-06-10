<?php

class shopSeofieldPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $category_filter = waRequest::get('category_filter', '');
        $product_filter = waRequest::get('product_filter', '');

        $limit = 30;

        $model = new shopSeofieldModel();

        $page_product = waRequest::get('page_product', 1);
        $offset_product = ($page_product-1) * $limit;

        $products = $model->getProducts($product_filter, $offset_product, $limit);

        $page_category = waRequest::get('page_category', 1);
        $offset_category = ($page_category-1) * $limit;

        $category = $model->getCategory($category_filter, $offset_category, $limit);

        $total_count = $model->getCount();


        $this->view->assign('total_category_count', $total_count['category']);
        $this->view->assign('pages_category', ceil($total_count['category']/$limit));
        $this->view->assign('category_filter', $category_filter);
        $this->view->assign('category', $category);

        $this->view->assign('total_product_count', $total_count['product']);
        $this->view->assign('pages_product', ceil($total_count['product']/$limit));
        $this->view->assign('product_filter', $product_filter);
        $this->view->assign('products', $products);
    }

}
