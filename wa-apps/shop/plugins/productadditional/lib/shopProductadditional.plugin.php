<?php


class shopProductadditionalPlugin extends shopPlugin
{
    public $products = [];
    public $view;

    public function __construct($info)
    {
        $this->view = wa()->getView();
		
        parent::__construct($info);
    }

    public static function settingCustomControl(){

        $model = new shopCategoryModel();
        $category = $model->getFullTree();

        $categories = array();
        $category_params_model = new shopCategoryParamsModel();
        $rows = $category_params_model->getByField('category_id', array_keys($category), true);
        foreach ($rows as $row) {
            if($row['name'] == 'product_additional')
                $categories[$row['category_id']] = $category[$row['category_id']];
        }

        if(!$categories)
            return false;

        $html = '';
        foreach ($categories as $c){
            $html .= '<a href="/webasyst/shop/?action=products#/products/category_id='.$c[id].'" target="_blank">'.$c[name].'</a><br/>';
        }

        return $html;
    }

    public static function getAdditionalProduct($category)
	{

        $plugin = wa('shop')->getPlugin('productadditional');
        $settings = $plugin->getSettings();

        if(!$category || empty($settings['active']))
            return false;

        $count = new shopProductsCollection('category/'.$category['id']);
        $category['count'] = $count->count();
		
        if($category['count'] <= $settings['category_count'])
		{
            if($category['parent_id'])
			{
                $model = new shopCategoryModel();
                $parent_categories = $model->getById($category['parent_id']);
                
                if($parent_categories)
				{
                    $product = [
                        'related_view' => 'thumbs' //thumbs,list,short-list
                    ];
					
					$wa_shop = new shopViewHelper(wa('shop'));
					$filtered = array_keys($wa_shop->products('category/' . $category['id'], null, null, ['fields' => 'id']));
		
					$collection = new shopProductsCollection('category/' . $parent_categories['id']);
			
					if($filtered)
						$collection->addWhere('id NOT IN (' . implode(',', $filtered) . ')');
					
					$products = $collection->getProducts('*', null, $settings['count'], true);

                    $view = wa()->getView();
                    $view->assign('pages_count', false);
                    $view->assign('product', $product);
                    $view->assign('products', $products);
                    $html = $view->fetch(wa()->getDataPath('themes', true, 'shop') . '/osnovnaja_new_header_footer_form/list-thumbs.html');

                    $html = '<div class="h3">Возможно, Вас заинтересует:</div>'.$html;

                    return $html;
                }
            }
        }
    }
}

