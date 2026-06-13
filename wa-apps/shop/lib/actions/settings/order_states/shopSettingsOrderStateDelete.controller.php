<?php

class shopSettingsOrderStateDeleteController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        if (!$id) {
            $this->errors = _w("Unknown state");
            return;
        }

        $userlog = shopUserlogPlugin::getInstance();
        $workflow_before = $userlog ? shopUserlogSettingsSnapshot::captureOrderWorkflow() : null;

        $order_model = new shopOrderModel();
        if ($order_model->countByField('state_id', $id)) {
            $this->errors = _w("Cannot delete order status while there are active orders in this status");
            return;
        }

        $config = shopWorkflow::getConfig();
        $state_name = ifset($config, 'states', $id, 'name', $id);
        if (isset($config['states'][$id])) {
            unset($config['states'][$id]);
        }
        shopWorkflow::setConfig($config);

        if ($userlog && $workflow_before !== null) {
            $userlog->logSettingsChange(
                'Статус заказа: '.$state_name,
                $workflow_before,
                shopUserlogSettingsSnapshot::captureOrderWorkflow()
            );
        }
    }
}