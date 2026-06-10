<?php

class shopCartsPluginReportUsageAction extends waViewAction
{


    public function preExecute()
    {
        $u = $this->getUser();

        if (!($u->isAdmin('shop') || $u->getRights('shop', 'carts_plugin.usage_settings'))) {
            throw new waRightsException(_w("Access denied"));
        }
    }

    public function  execute()
    {
        $db_config = wa()->getConfigPath().'/db.php';
        if(!file_exists($db_config))
            return;

        $db = include $db_config;
        if(empty($db['default']) || empty($db['default']['database']))
            return;
        $db = $db['default']['database'];
        try {
            $model = new waModel();
            $info = $model->query('SELECT table_name AS "table", (data_length + index_length) "size" FROM information_schema.TABLES WHERE table_schema = ? AND table_name LIKE "shop_carts_plugin%"', $db)->fetchAll('table', true);

            $memory = $info['shop_carts_plugin_log'] + $info['shop_carts_plugin_storefront'] + $info['shop_carts_plugin_contact'];
            $memory = waFiles::formatSize($memory, '%0.2f', _wp('Bytes,KBytes,MBytes,GBytes'));
            $this->view->assign('memory', $memory);
        } catch(waDbException $e) {
            // ну и ладно.
        }
    }
}