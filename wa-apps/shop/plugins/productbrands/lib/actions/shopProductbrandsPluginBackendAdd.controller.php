<?php

/**
 * Created by PhpStorm.
 * User: neeil
 * Date: 21.06.2016
 * Time: 11:33
 */
class shopProductbrandsPluginBackendAddController extends waJsonController
{

    public function execute()
    {

        $model = new shopProductbrandAddModel();
        $brands_model = new shopProductbrandsModel();
        $brands_lat = new shopProductbrandsPluginBackendSaveController();

        for ($i = 1; $i <= 1000; $i++) {
          $one = $model->getByField('value', 'New Brands'.' '.$i);
            if($one == null){
                $value = 'New Brands'.' '.$i;
                $data = array(
                    'feature_id' => '6',
                    'value' => $value
                );
                $id = $model->insert($data);
                $brands_model->insert(array('url' => $brands_lat->rusToLat($value),'name' => $value,'id' => $id));
                break;
            }
        }


        $this->redirect('/webasyst/shop/?action=products#/brands/');


    }

}