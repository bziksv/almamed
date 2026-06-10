<?php

/*
 * Class shopYossPluginSettingsAction
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopYossPluginSettingsAction extends waViewAction {

    public function execute() {
    	$plugin = wa('shop')->getPlugin('yoss');
        $namespace = 'shop_yoss';

        $params = array();
        $params['id'] = 'yoss';
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings = $plugin->getSettings();
        $settings_controls = $plugin->getControls($params);

        $this->view->assign('settings_controls', $settings_controls);
    }

}