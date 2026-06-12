<?php

class shopProductmanagerPluginPoolController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $service = new shopProductmanagerService();
        $manager_ids = waRequest::post('manager_ids', array(), waRequest::TYPE_ARRAY_INT);
        $saved = $service->saveManagerPool($manager_ids);

        $this->response = array(
            'status' => 'ok',
            'manager_ids' => $saved,
        );
    }
}
