<?php

class shopNbpopupformPluginBackendLayoutAction extends waViewAction
{

    public function execute(){

        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w("Access denied"));
        }

        $this->setLayout(new shopBackendLayout());

        $this->layout->assign('no_level2', true);
        $this->getResponse()->setTitle(_w('Settings'));
    }


}