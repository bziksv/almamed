<?php

class userlogBackendLayout extends waLayout
{
    public function execute()
    {
        $this->view->assign('can_rollback', wa()->getUser()->getRights('userlog', 'rollback'));
        $this->view->assign('can_trash', wa()->getUser()->getRights('userlog', 'trash'));
    }
}
