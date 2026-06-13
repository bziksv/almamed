<?php
class shopSettingsPaymentDeleteController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        if ($plugin_id = waRequest::post('plugin_id')) {
            $userlog = shopUserlogPlugin::getInstance();
            $plugin_row = (new shopPluginModel())->getByField(array('id' => $plugin_id, 'type' => 'payment'));
            $settings_before = $userlog && $plugin_row
                ? shopUserlogSettingsSnapshot::capturePluginInstance($plugin_row, 'payment')
                : null;

            $model = new shopPluginModel();
            if ($plugin = $plugin_row) {
                $name = ifset($plugin, 'name', '#'.$plugin_id);
                $settings_model = new shopPluginSettingsModel();
                $settings_model->del($plugin['id'], null);
                $model->deleteById($plugin['id']);

                if ($userlog && $settings_before !== null) {
                    $userlog->logSettingsChange(
                        'Оплата: '.$name,
                        $settings_before,
                        array()
                    );
                }
            } else {
                throw new waException("Payment plugin {$plugin_id} not found", 404);
            }

        }
    }
}
