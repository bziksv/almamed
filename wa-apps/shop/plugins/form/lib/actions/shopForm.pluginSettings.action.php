<?php

class shopFormPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('form');
        $settings = $plugin->getSettings();
        $this->view->assign('settings', $settings);
    }
}
