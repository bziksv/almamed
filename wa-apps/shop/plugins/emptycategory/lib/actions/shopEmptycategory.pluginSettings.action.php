<?php

class shopEmptycategoryPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $feature_id = 6;

        $collection = new shopProductsCollection('', ['params' => 'buy']);
        $collection->addWhere("p.category_id IS NULL");
        $total_count = $collection->count();

        $page = waRequest::get('page', 1);
        $limit = 30;
        $offset = ($page-1) * $limit;

        $products = $collection->getProducts("*,image_crop_small,feature_$feature_id", $offset, $limit);

        $this->view->assign('total_count', $total_count);
        $this->view->assign('pages', ceil($total_count/$limit));
        $this->view->assign('products', $products);
    }

}
