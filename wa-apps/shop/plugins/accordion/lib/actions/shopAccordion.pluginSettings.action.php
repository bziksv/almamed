<?php

class shopAccordionPluginSettingsAction extends waViewAction
{

    public function execute(){


        $plugin = wa('shop')->getPlugin('accordion');
            $settings = $plugin->getSettings();
            $this->view->assign('settings', $settings);
    }
}