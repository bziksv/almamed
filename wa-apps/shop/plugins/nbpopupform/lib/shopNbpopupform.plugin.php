<?php

Class shopNbpopupformPlugin extends shopPlugin {


    public function backend_menu(){
        return array(
            'core_li' => '<li class="no-tab"><a href="?plugin=nbpopupform&action=layout#/general/">Формы</a></li>'
        );
    }



    public static function getForm($id){

        $fields = new shopNbpopupformFormsinputModel();
        $form = new shopNbpopupformFormsModel();

        $form = $form->getById($id);
        $field = $fields->getByField('id_form', $id, true);

        $view = wa()->getView();
        $view->assign('fields', $field);
        $view->assign('form', $form);

        return $view->fetch($_SERVER['DOCUMENT_ROOT'].'/wa-apps/shop/plugins/nbpopupform/templates/frontend.html');
    }







}