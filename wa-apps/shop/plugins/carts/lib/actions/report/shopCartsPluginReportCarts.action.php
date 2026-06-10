<?php

class shopCartsPluginReportCartsAction extends waViewAction
{

    public function preExecute()
    {
        $u = $this->getUser();

        if (!($u->isAdmin('shop') || $u->getRights('shop', 'carts_plugin.contacts_report'))) {
            throw new waRightsException(_w("Access denied"));
        }
    }

    public function  execute()
    {
        /**
         * @todo
         */
        $on_page = 25;

        $start = microtime(true);

        $model = new shopCartsPluginStorefrontModel();

        $hash = waRequest::get('hash', 0, waRequest::TYPE_STRING_TRIM);
        $hash = preg_split('#/#', $hash, 2, PREG_SPLIT_NO_EMPTY);
        $where = array(
            'timeframe' => waRequest::get('timeframe'),
            'from' => waRequest::get('from'),
            'to' => waRequest::get('to'),
            'hash' => $hash,
        );

        $page = waRequest::get('page', 1, waRequest::TYPE_INT);
        if($page < 1) $page = 1;
        $offset = ($page - 1) * $on_page;


        $data = $model->getReportData($where, $offset, $on_page);

        $pages_total = ceil($data['total'] / $on_page);

        $this->view->assign(array(
            'data' => $data,
            'pages_total' => $pages_total,
            'generated' => microtime(true)-$start,
            'hash' => $hash,
            'lang' => substr(wa()->getLocale(), 0, 2)
        ));
    }
}