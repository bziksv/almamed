<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopWholesalePluginSettingsAction extends waViewAction {

    public function execute() {
        $this->view->assign(array(
            'templates' => shopWholesalePlugin::$templates,
            'plugin' => wa()->getPlugin('wholesale'),
            'route_hashs' => shopWholesaleRouteHelper::getRouteHashs(),
        ));
    }

}
