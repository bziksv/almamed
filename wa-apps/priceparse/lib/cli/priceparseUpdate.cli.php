<?php

class priceparseUpdateCli extends waCliController
{
    public function execute()
    {

        $model = new priceparseModel();
        $data = $model->select('*')->fetchAll();
        if(count($data)){
            foreach ($data as $item){
                if($item['selector'] && $item['link']){
                    $html = new priceparseParser($item['link']);
                    $price = $html->find($item['selector']);
                    if(strlen(trim($price)) > 0){
                        $model->updateByField('id_product', $item['id_product'], array('price' => trim($price)));
                    }
                }
            }
        }

    }

}
