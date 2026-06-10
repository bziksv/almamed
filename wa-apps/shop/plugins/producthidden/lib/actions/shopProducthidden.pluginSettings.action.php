<?php

class shopProducthiddenPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $feature_id = 6;

        $categoryModel = new shopCategoryModel();
        $collection = new shopProductsCollection('', ['params' => 'buy']);
        $collection->addWhere("p.status = 0");
        $total_count = $collection->count();

        $page = waRequest::get('page', 1);
        $limit = 30;
        $offset = ($page-1) * $limit;

        $products = $collection->getProducts("*,image_crop_small,feature_$feature_id", $offset, $limit);

        foreach($products as &$product){

            $category = $categoryModel->getById($product['category_id']);
            if($category['parent_id'])
                $category['parent'] = $categoryModel->getById($category['parent_id']);

            $product['category'] = $category;
        }

        $this->view->assign('total_count', $total_count);
        $this->view->assign('pages', ceil($total_count/$limit));
        $this->view->assign('products', $products);
    }

}
