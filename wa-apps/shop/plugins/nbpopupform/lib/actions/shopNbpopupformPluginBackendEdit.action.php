<?php

Class shopNbpopupformPluginBackendEditAction extends waViewAction{


    public function execute(){
        $get = waRequest::get('id_form');
        if($get){
            $data = array();
            $modelForm = new shopNbpopupformFormsModel();
            $data['form'] = $modelForm->getByField('id', $get);

            $modelInput = new shopNbpopupformFormsinputModel();
            $data['fields'] = $modelInput->getByField('id_form', $get, true);
            $this->view->assign('data', $data);
            $this->view->assign('id', $get);
        }
    }
}