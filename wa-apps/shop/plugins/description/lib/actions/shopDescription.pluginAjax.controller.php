<?php

class shopDescriptionPluginAjaxController extends waJsonController
{

    public function execute()
    {
        $page = (int)waRequest::post('page');
        $start = ($page) ? ($page - 1) * shopDescriptionPlugin::$limit : $page;

        if(is_numeric($start)){
            $product = new shopDescriptionPlugin(false);
            $products = $product->getEmptyField(false,$start);
            $this->response = array(
                'products' => $products,
            );
        }
    }
}