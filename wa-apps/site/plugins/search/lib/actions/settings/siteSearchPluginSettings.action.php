<?php


class siteSearchPluginSettingsAction extends waViewAction {

    public function execute()
    {
        $this->view->assign('cron_command', 'php '.wa()->getConfig()->getRootPath().'/cli.php site searchPlugin');
    }

}