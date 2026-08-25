<?php

class shopLeadsPluginReportStatusController extends waJsonController
{
    public function execute()
    {
        $u = $this->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $ids = waRequest::post('ids', array(), waRequest::TYPE_ARRAY_INT);
        $status = waRequest::post('status', '', waRequest::TYPE_STRING_TRIM);

        $model = new shopLeadsPluginLeadModel();
        $n = $model->updateStatusByIds($ids, $status);

        $this->response = array(
            'updated'   => $n,
            'new_count' => $model->countNew(),
        );
    }
}
