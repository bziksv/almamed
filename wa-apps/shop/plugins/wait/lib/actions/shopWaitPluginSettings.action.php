<?php

class shopWaitPluginSettingsAction extends waViewAction {

    public function execute() {
	
        $plugin = wa('shop')->getPlugin('wait');
        // получаем все настройки плагина, чтобы передать их в шаблон
        $settings = $plugin->getSettings();

        $this->view->assign('settings', $settings);

    }

}
