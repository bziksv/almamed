<?php

class shopPlugmeinPluginBackendController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }
        if (waRequest::get('id')=='savelist') {
            $this->saveList();
        }
        if (waRequest::post('id')=='run') {
            $this->generateConfig();
        }
    }
    
    private function saveList()
    {
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getConfigPath('plugins.php', true);
        waFiles::readFile($path, "plugins.txt");
    }
    
    private function generateConfig()
    {
        $plugins = waRequest::post();
        unset($plugins['id']);
        foreach ($plugins as &$value) {
            $value = (bool) $value;
        }
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getConfigPath('plugins.php', true);
        $plugin_php = include $path;
        foreach ($plugins as $plugin => $state) {
            $plugin_php[$plugin] = $state;
        }
        waUtils::varExportToFile($plugin_php, $path, true);
        if (wa()->appExists('installer')) {
            wa('installer');
            installerHelper::flushCache();
        }
    }
}
