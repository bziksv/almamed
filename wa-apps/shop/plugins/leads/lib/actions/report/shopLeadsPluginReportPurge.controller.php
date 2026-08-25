<?php

class shopLeadsPluginReportPurgeController extends waJsonController
{
    public function execute()
    {
        $u = $this->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $plugin = wa('shop')->getPlugin('leads');
        $months = (int) $plugin->getSettings('retention_months');
        if ($months <= 0) {
            $this->response = array('deleted' => 0, 'message' => 'Очистка отключена (срок = 0)');
            return;
        }

        $model = new shopLeadsPluginLeadModel();
        $deleted = $model->purgeOlderThanMonths($months);

        $this->response = array(
            'deleted' => $deleted,
            'months'  => $months,
            'message' => $deleted
                ? ('Удалено заявок старше ' . $months . ' мес.: ' . $deleted)
                : ('Нет заявок старше ' . $months . ' мес.'),
        );
    }
}
