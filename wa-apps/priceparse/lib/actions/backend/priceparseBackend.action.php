<?php

class priceparseBackendAction extends waViewAction
{
    public function execute(){
        $model_products = new priceparseProductModel();
        $this->view->assign('products', $model_products->getProductsWithParse());
    }
}
