<?php

class shopProductmanagerPluginDialogManagerAction extends waViewAction
{
    public function execute()
    {
        $users = wa('shop')->getPlugin('productmanager');

        $count = waRequest::post('count', "string");
        $this->view->assign('users', $users->get_users());
        $this->view->assign('count', $count);
    }
}