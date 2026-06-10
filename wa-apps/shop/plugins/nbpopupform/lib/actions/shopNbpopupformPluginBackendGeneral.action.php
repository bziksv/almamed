<?php

Class shopNbpopupformPluginBackendGeneralAction extends waViewAction{


    public function execute(){

        $model = new shopNbpopupformFormsModel();
        $data = $model->getAll();
        $this->view->assign('forms',$data);

    }


}