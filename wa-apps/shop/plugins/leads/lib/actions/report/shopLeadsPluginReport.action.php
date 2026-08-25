<?php

class shopLeadsPluginReportAction extends waViewAction
{
    public function execute()
    {
        $this->checkRights();

        $this->getResponse()->setTitle('Заявки');
        $this->getResponse()->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->getResponse()->addHeader('Pragma', 'no-cache');
        $this->getResponse()->addHeader('Expires', '0');

        $layout = new shopBackendLayout();
        $layout->assign('no_level2', true);
        $this->setLayout($layout);

        $filters = array(
            'source'          => waRequest::get('source', '', waRequest::TYPE_STRING_TRIM),
            'status'          => waRequest::get('status', '', waRequest::TYPE_STRING_TRIM),
            'date_from'       => waRequest::get('date_from', '', waRequest::TYPE_STRING_TRIM),
            'date_to'         => waRequest::get('date_to', '', waRequest::TYPE_STRING_TRIM),
            'q'               => waRequest::get('q', '', waRequest::TYPE_STRING_TRIM),
            'hide_duplicates' => waRequest::get('hide_duplicates', 0, waRequest::TYPE_INT) ? 1 : 0,
        );

        $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $model = new shopLeadsPluginLeadModel();
        $total = $model->countFiltered($filters);
        $leads = $model->getFiltered($filters, $offset, $limit);
        $pages = max(1, (int) ceil($total / $limit));

        $export_params = array_merge(
            array('plugin' => 'leads', 'module' => 'report', 'action' => 'export'),
            $filters
        );
        $export_url = '?' . http_build_query($export_params);

        $this->view->assign(array(
            'leads'             => $leads,
            'filters'           => $filters,
            'total'             => $total,
            'page'              => $page,
            'pages'             => $pages,
            'limit'             => $limit,
            'source_labels'     => shopLeadsPlugin::sourceLabels(),
            'status_labels'     => shopLeadsPlugin::statusLabels(),
            'status_labels_json'=> json_encode(shopLeadsPlugin::statusLabels(), JSON_UNESCAPED_UNICODE),
            'new_count'         => $model->countNew(),
            'export_url'        => $export_url,
            'plugin_url'        => wa()->getAppStaticUrl('shop') . 'plugins/leads/',
            'retention_months'  => (int) wa('shop')->getPlugin('leads')->getSettings('retention_months'),
        ));
    }

    protected function checkRights()
    {
        $u = $this->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }
    }
}
