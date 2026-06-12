<?php

class shopProductmanagerPluginStatsController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $service = new shopProductmanagerService();
        $hide_empty = (bool) waRequest::get('hide_empty', 0, waRequest::TYPE_INT);

        $this->response = array(
            'status' => 'ok',
            'summary' => $service->getSummary(),
            'categories' => $service->getCategoryRows($hide_empty),
            'managers' => $service->getManagers(),
        );
    }
}
