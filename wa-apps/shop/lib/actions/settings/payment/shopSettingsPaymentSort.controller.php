<?php
class shopSettingsPaymentSortController extends waJsonController
{
    public function execute()
    {

        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        $id = waRequest::post('module_id', 0, waRequest::TYPE_INT);
        $after_id = waRequest::post('after_id', 0, waRequest::TYPE_INT);
        $userlog = shopUserlogPlugin::getInstance();
        $order_before = $userlog ? shopUserlogSettingsSnapshot::capturePluginsOrder(shopPluginModel::TYPE_PAYMENT) : null;

        $model = new shopPluginModel();
        try {
            $model->move($id, $after_id, shopPluginModel::TYPE_PAYMENT);
            if ($userlog && $order_before !== null) {
                $userlog->logSettingsChange(
                    'Оплата: порядок',
                    $order_before,
                    shopUserlogSettingsSnapshot::capturePluginsOrder(shopPluginModel::TYPE_PAYMENT)
                );
            }
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }
}
