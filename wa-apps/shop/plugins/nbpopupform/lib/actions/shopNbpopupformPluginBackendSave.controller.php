<?php

class shopNbpopupformPluginBackendSaveController extends waController
{

    public function execute()
    {

        $data = waRequest::post('nbpopupform');

        $formModel = new shopNbpopupformFormsModel();
        $id_form = $formModel->insert($data['form']);

        array_pop($data['fields']);
        $formInputModel = new shopNbpopupformFormsinputModel();
        foreach($data['fields'] as $d){
            if(empty($d['disabled'])){
                $d['id_form'] = $id_form;
                $formInputModel->insert($d);
            }
        }

        $this->redirect('/webasyst/shop/?plugin=nbpopupform&action=layout#/general/');

    }

}