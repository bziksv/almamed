<?php

class shopLeadsPluginReportViewAction extends waViewAction
{
    public function execute()
    {
        $u = $this->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $id = waRequest::get('id', 0, waRequest::TYPE_INT);
        $model = new shopLeadsPluginLeadModel();
        $lead = $model->getById($id);
        if (!$lead) {
            throw new waException('Заявка не найдена', 404);
        }

        if (waRequest::post('save_status')) {
            $status = waRequest::post('status', '', waRequest::TYPE_STRING_TRIM);
            $allowed = array_keys(shopLeadsPlugin::statusLabels());
            if (in_array($status, $allowed, true)) {
                $model->updateById($id, array('status' => $status));
                $lead['status'] = $status;
            }
        }

        $this->getResponse()->setTitle('Заявка #' . $id);
        $this->getResponse()->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->getResponse()->addHeader('Pragma', 'no-cache');

        $layout = new shopBackendLayout();
        $layout->assign('no_level2', true);
        $this->setLayout($layout);

        $this->view->assign(array(
            'lead'           => $lead,
            'source_labels'  => shopLeadsPlugin::sourceLabels(),
            'status_labels'  => shopLeadsPlugin::statusLabels(),
            'plugin_url'     => wa()->getAppStaticUrl('shop') . 'plugins/leads/',
        ));
    }
}
