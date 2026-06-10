<?php

class shopEmptybrandPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $feature_id = 6;

        $categoryModel = new shopCategoryModel();
        $collection = new shopProductsCollection('');
        $collection->addWhere("id NOT IN(SELECT p.id FROM shop_product p JOIN shop_product_features pf1 ON p.id = pf1.product_id AND pf1.feature_id = $feature_id)");
        $total_count = $collection->count();

        $page = waRequest::get('page', 1);
        $limit = 30;
        $offset = ($page-1) * $limit;

        //Product with empty brand.
        $products = $collection->getProducts("*,image_crop_small,feature_$feature_id", $offset, $limit);

        foreach($products as &$product){

            $category = $categoryModel->getById($product['category_id']);
            if($category['parent_id'])
                $category['parent'] = $categoryModel->getById($category['parent_id']);

            $product['category'] = $category;
        }

        $feature_model = new shopFeatureModel();
        $brand = $feature_model->getFeatures('code', 'brend');
        $brands_values = $feature_model->getValues($brand);
        $features_brands = $brands_values[$feature_id];
        sort($features_brands['values']);

        $this->view->assign('total_count', $total_count);
        $this->view->assign('pages', ceil($total_count/$limit));
        $this->view->assign('features_brand', $features_brands);
        $this->view->assign('products', $products);
    }

}
