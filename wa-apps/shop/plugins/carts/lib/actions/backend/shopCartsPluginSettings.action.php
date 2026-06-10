<?php

class shopCartsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        /**
         * @var shopConfig $shop_config
         */
        $shop_config =  wa('shop')->getConfig();
        $this->view->assign('default_sender', $shop_config->getGeneralSettings('email'));
        $this->view->assign('default_sender_name', $shop_config->getGeneralSettings('name'));

        /**
         * @var shopCartsPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin('carts');

        $settings = $plugin->getControls(array(
            'id' => 'carts',
            'namespace' => 'shop_carts',
            'title_wrapper' => '%s',
            'description_wrapper' => '[<span title="%s">?</span>]',
            'control_wrapper' => '<div class="name">%1$s %3$s</div><div class="value">%2$s</div>'
        ));

        $this->view->assign('cron', array(
            'command' => 'php '.wa()->getConfig()->getRootPath().'/cli.php shop cartsPluginCheck',
            'cron'    => $plugin->getSettings('last_check'),
        ));

        $this->view->assign('routes', wa()->getRouting()->getByApp('shop'));
        $this->view->assign('whatsnew', $plugin->whatsnew());
        $this->view->assign('settings', $settings);
    }
}
