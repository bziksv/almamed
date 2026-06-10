<?php

class shopDescriptionPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $product = new shopDescriptionPlugin(false);
        $products = $product->getEmptyField(false,0);
        $this->view->assign('products', $products);
        $this->view->assign('count', $product->count);
        $this->view->assign('pagination', ceil($product->count/shopDescriptionPlugin::$limit));
    }

}