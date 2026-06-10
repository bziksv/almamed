<?php

class priceparseAjaxActions extends waJsonActions
{

    function saveAction()
    {
        $data = waRequest::get();

        $model = new priceparseModel();
        if(strlen(trim($data['value'])) > 0){
            if($model->countByField("id_product", $data['product_id'])){
                $model->updateByField('id_product', $data['product_id'], array($data['field'] => $data['value']));
            }else{
                $model->insert(array('id_product' => $data['product_id'], $data['field'] => $data['value']));
            }
        }
        $this->response = $data;
    }

    function deleteAction()
    {
        $data = waRequest::get();
        $model = new priceparseModel();
        if($data['product_id'] && $model->countByField("id_product", $data['product_id'])){
            $model->deleteByField('id_product', $data['product_id']);
        }
        $this->response = $data;
    }

}