<?php

class shopCategoryfinderPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $plugin = wa('shop')->getPlugin('categoryfinder');

        $this->view->assign(array(
            'plugin_url' => wa()->getAppUrl('shop') . '?plugin=categoryfinder',
            'plugin_version' => $plugin->getVersion(),
        ));
    }
}
