<?php

class shopNbpopupformPluginBackendUpdateController extends waController
{

    public function execute()
    {
        $data = waRequest::post('nbpopupform');
        array_pop($data['fields']);
        $formModel = new shopNbpopupformFormsModel();
        $formModel->updateById($data['id'], $data['form']);

        $formInputModel = new shopNbpopupformFormsinputModel();
        $formInputModel->deleteByField(array("id_form" => $data['id']));

        foreach($data['fields'] as $d){
            if(empty($d['disabled'])){
                $d['id_form'] = $data['id'];
                $formInputModel->insert($d);
            }
        }
        $this->redirect('/webasyst/shop/?plugin=nbpopupform&action=layout#/edit/'.$data['id'].'/');
    }
}