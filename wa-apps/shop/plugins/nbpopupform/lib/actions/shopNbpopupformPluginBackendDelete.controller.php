<?php

class shopNbpopupformPluginBackendDeleteController extends waJsonController
{

    public function execute()
    {
        $get = waRequest::get('id_form');
        if($get){
            $formModel = new shopNbpopupformFormsModel();
            $formModel->deleteById((int)$get);

            $formInputModel = new shopNbpopupformFormsinputModel();
            $formInputModel->deleteByField(array("id_form" => (int)$get));
            $this->response = array('data' => $get);
        }
    }
}