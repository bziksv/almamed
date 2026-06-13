<?php
class shopSettingsShippingDeleteController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        if ($plugin_id = waRequest::post('plugin_id')) {
            $userlog = shopUserlogPlugin::getInstance();
            $settings_before = $userlog
                ? shopUserlogSettingsSnapshot::captureShippingPluginById($plugin_id)
                : null;

            $model = new shopPluginModel();
            if ($plugin = $model->getByField(array('id' => $plugin_id, 'type' => 'shipping'))) {
                $name = ifset($plugin, 'name', '#'.$plugin_id);
                $model->deleteById($plugin['id']);

                if ($userlog && $settings_before !== null) {
                    $userlog->logSettingsChange(
                        'Доставка: '.$name,
                        $settings_before,
                        array()
                    );
                }
            } else {
                throw new waException("Shipping plugin {$plugin_id} not found", 404);
            }

        }
    }
}
