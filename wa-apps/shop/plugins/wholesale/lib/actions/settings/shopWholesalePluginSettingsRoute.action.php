<?php

class shopWholesalePluginSettingsRouteAction extends waViewAction {

    public function execute() {
        $route_hash = waRequest::get('route_hash');
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set(array('shop', 'wholesale'), 'route_hash', $route_hash);
        $plugin_model = new shopPluginModel();
        $view = wa()->getView();
        $view->assign(array(
            'route_hash' => $route_hash,
            'route_settings' => shopWholesaleRouteHelper::getRouteSettings($route_hash),
            'templates' => shopWholesaleRouteHelper::getRouteTemplates($route_hash),
            'currency' => wa('shop')->getConfig()->getCurrency(true),
            'instances' => $plugin_model->listPlugins(shopPluginModel::TYPE_SHIPPING, array('all' => true)),
        ));
    }

}
