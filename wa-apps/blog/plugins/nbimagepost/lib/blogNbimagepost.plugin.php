<?php
Class blogNbimagepostPlugin extends blogPlugin{



    public function backendPostEdit($params){

        $view = wa()->getView();
        $view->assign('id_post',$params['id']);
        $view->assign('image',$params['image']);
        return array(
            'sidebar' => $view->fetch($_SERVER['DOCUMENT_ROOT'].'/wa-apps/blog/plugins/nbimagepost/templates/sideBarUpload.html'),
        );
    }

    public function blogHeader(){

            $this->addCss('css/nbimagepost.css');
            $this->addJs('js/script.js');

    }


}