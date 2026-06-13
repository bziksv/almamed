<?php

class shopSettingsOrderActionDeleteController extends waJsonController
{
    public function execute()
    {
        $userlog = shopUserlogPlugin::getInstance();
        $workflow_before = $userlog ? shopUserlogSettingsSnapshot::captureOrderWorkflow() : null;

        $id = waRequest::post('id');
        if (!$id) {
            $this->errors = _w("Unknown action");
            return;
        }


        $config = shopWorkflow::getConfig();
        if (isset($config['actions'][$id])) {
            unset($config['actions'][$id]);
        }
        shopWorkflow::setConfig($config);

        if ($userlog && $workflow_before !== null) {
            $userlog->logSettingsChange(
                'Действие заказа: '.$id,
                $workflow_before,
                shopUserlogSettingsSnapshot::captureOrderWorkflow()
            );
        }
    }
}