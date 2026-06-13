<?php
class shopSettingsPaymentSaveController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        if ($plugin = waRequest::post('payment')) {
            $userlog = shopUserlogPlugin::getInstance();
            $settings_before = $userlog
                ? shopUserlogSettingsSnapshot::capturePluginInstance($plugin, 'payment')
                : null;
            try {
                if (!isset($plugin['settings'])) {
                    $plugin['settings'] = array();
                }
                $saved = shopPayment::savePlugin($plugin);
                $this->response['message'] = _w('Saved');
                if ($userlog && $settings_before !== null) {
                    $after = shopUserlogSettingsSnapshot::capturePluginInstance($saved, 'payment');
                    $name = ifset($after, 'name', ifset($settings_before, 'name', ifset($plugin, 'plugin', 'оплата')));
                    $userlog->logSettingsChange('Оплата: '.$name, $settings_before, $after);
                }
            } catch (waException $ex) {
                $this->setError($ex->getMessage());
            }
        }
    }
}
