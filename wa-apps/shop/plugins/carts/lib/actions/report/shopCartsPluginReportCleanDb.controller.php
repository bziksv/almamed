<?php


class shopCartsPluginReportCleanDbController extends waJsonController
{

    public function preExecute()
    {
        $u = $this->getUser();

        if (!($u->isAdmin('shop') || $u->getRights('shop', 'carts_plugin.usage_settings'))) {
            throw new waRightsException(_w("Access denied"));
        }
    }

    public function execute()
    {
        $csm = new shopCartsPluginStorefrontModel();
        $csrm = new shopCartsPluginStorefrontRefererModel();
        $clm = new shopCartsPluginLogModel();
        $ccm = new shopCartsPluginContactModel();
        $days = waRequest::post('days', 0, waRequest::TYPE_INT);
        if($days > 0) {
            $codes = $csm->select('code')->where('edit_datetime < (NOW() - interval '.$days.' day)')->fetchAll(null, true);
            $ids = $csm->select('id')->where('edit_datetime < (NOW() - interval '.$days.' day)')->fetchAll(null, true);
            if($codes) {
                $ccm->deleteByField('code', $codes);
            }
            if($ids) {
                $clm->deleteByField('storefront_id', $ids);
                $csrm->deleteByField('storefront_id', $ids);
            }
            $clm->query('DELETE FROM '.$clm->getTableName().' WHERE sent < (NOW() - interval '.$days.' day)');
            $csm->query('DELETE FROM '.$csm->getTableName().' WHERE edit_datetime < (NOW() - interval '.$days.' day)');

            try {
                $clm->query('OPTIMIZE TABLE '.$clm->getTableName());
                $ccm->query('OPTIMIZE TABLE '.$ccm->getTableName());
                $csm->query('OPTIMIZE TABLE '.$csm->getTableName());
                $csm->query('OPTIMIZE TABLE '.$csrm->getTableName());
            } catch(waDbException $e) {
                waLog::log('carts plugin - optimize table error');
            }
        }
    }
}