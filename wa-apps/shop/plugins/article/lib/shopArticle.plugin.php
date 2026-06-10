<?php
class shopArticlePlugin extends shopPlugin
{
    public function saveSettings($settings = array()) {
        $model = new waModel();
        $result = $model->query("SELECT * FROM shop_product_skus WHERE sku = ''");
        $data = $result->fetchAll();
        if($data){
            $update = array();
            foreach($data as $d){
                if(empty($d['sku'])){
                    $update[$d['id']] = $d['product_id'];
                }
            }

            foreach($update as $id => $sku){
                $model->query("UPDATE shop_product_skus SET sku = 'A-$sku' WHERE id = $id");
            }
        }
        parent::saveSettings(array('article' => array('count' => count($update))));
        return array('messages' => 'Заполнено '.count($update).' артикулов.');
    }
}