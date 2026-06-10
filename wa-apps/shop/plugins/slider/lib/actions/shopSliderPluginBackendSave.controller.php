<?php

class shopSliderPluginBackendSaveController extends waJsonController
{
    public function execute()
    {
        $model = new shopSliderModel();

        $sort = waRequest::post('sort');
        $link = waRequest::post('link');
        $alt = waRequest::post('alt');
        $img_path = waRequest::post('img_path');

        $files = array();
        foreach($_FILES['img']['error'] as $key => $img){
            if ($img) {
                if($img_path[$key]){
                    $files[$key] = $img_path[$key];
                }else{
                    $files[$key] = $img;
                }
            }
            else {
                $format = array('/png','/jpeg');

                if(in_array(strstr($_FILES['img']['type'][$key],'/'), $format)){
                    $file_name = $_FILES['img']['name'][$key];
                    $files[$key] = '/wa-data/public/shop/slider/img/' . $file_name;
                    $files_root = $_SERVER['DOCUMENT_ROOT'].$files[$key];

                    waFiles::create(wa()->getConfig()->getPath('data').'/public/shop/slider/img');

                    $image = waImage::factory($_FILES['img']['tmp_name'][$key]);
                    $image->save($files_root, 100);

                    $image_sm = waImage::factory($_FILES['img']['tmp_name'][$key]);
                    $image_sm->resize(576, 220, 'WIDTH');
                    $image_sm->save(str_replace('/img/','/img/sm_',$files_root), 100);

                }else{
                    $files[$key] = false;
                }
            }
        }

        $records = $model->order('sort ASC')->fetchAll();
        foreach($records as $r){
            $model->deleteById($r['id']);
        }

        foreach($files as $key => $db){

            $model->insert(array(
                "sort" => $sort[$key],
                "link" => $link[$key],
                "alt" => $alt[$key],
                "img" => $db
            ));
        }

        $this->redirect('/webasyst/shop/?action=plugins#/slider/');
    }

}