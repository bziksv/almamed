<?php
class shopSettingsShippingSaveController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        if ($plugin = waRequest::post('shipping')) {
            $userlog = shopUserlogPlugin::getInstance();
            $settings_before = $userlog
                ? shopUserlogSettingsSnapshot::capturePluginInstance($plugin, 'shipping')
                : null;
            try {
                if (!isset($plugin['settings'])) {
                    $plugin['settings'] = array();
                }
                $saved = shopShipping::savePlugin($plugin);
                $this->response['message'] = _w('Saved');
                if ($userlog && $settings_before !== null) {
                    $after = shopUserlogSettingsSnapshot::capturePluginInstance($saved, 'shipping');
                    $name = ifset($after, 'name', ifset($settings_before, 'name', ifset($plugin, 'plugin', 'доставка')));
                    $userlog->logSettingsChange('Доставка: '.$name, $settings_before, $after);
                }
            } catch (waException $ex) {
                $this->setError($ex->getMessage());
            }
        }
    }
}
