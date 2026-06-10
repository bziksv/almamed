<?php

class shopArticlePluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('article');
        // получаем все настройки плагина, чтобы передать их в шаблон
        $settings = $plugin->getSettings();
        $this->view->assign('settings', $settings);
    }

}