<?php

class blogAjaxActions extends waJsonActions
{

    function uploadAction()
    {
        if ($_FILES['file']['error']) {
            $files = $_FILES['file']['error'];
        }
        else {
            $format = array('/png','/jpeg');
            if(in_array(strstr($_FILES['file']['type'],'/'), $format)){
                $file_name = md5($_FILES['file']['name']).str_replace('/','.',strstr($_FILES['file']['type'],'/'));
                $files = '/wa-data/public/blog/img/' . $file_name;
                $files_root = $_SERVER['DOCUMENT_ROOT'].$files;
                waFiles::create(wa()->getConfig()->getPath('data').'/public/blog/img');
                move_uploaded_file($_FILES['file']['tmp_name'], $files_root);
            }else{
                $files = false;
            }
        }
        $this->response = $files;
    }


    function cropimageAction(){
        $x = waRequest::post('x');
        $y = waRequest::post('y');
        $w = waRequest::post('w');
        $h = waRequest::post('h');
        $id = waRequest::post('id');
        $href = waRequest::post('href');

        if($w and $h and $href){
             $new_home_path = '/public/blog/img/'.ceil($w).'x'.ceil($h);
             $new_root_path = wa()->getConfig()->getPath('data');
             $new_file_name = md5($href).strstr($href,'.');

             waFiles::create($new_root_path.$new_home_path);

             $image = waImage::factory(wa()->getConfig()->getRootPath().$href);
             $image->crop($w, $h, $x, $y);
             $image->resize(485,false);
             $image->save($new_root_path.$new_home_path.'/'.$new_file_name, 100);
             $model = new blogNbimagepostModel();
             $model->updateById($id, array("image" => '/wa-data'.$new_home_path.'/'.$new_file_name));
             $this->response = '/wa-data'.$new_home_path.'/'.$new_file_name;
        }
    }
}