<?php

class shopDescriptionPluginUpdateController extends waJsonController
{

    public function execute()
    {

        $id = waRequest::post('id');
        $status = waRequest::post('status');
        $product = new shopDescriptionPlugin(false);

        if($id){
            $result = $product->updateProduct($id,$status);
        }

        $this->response = array(
            'desc' => $result,
        );

    }

}