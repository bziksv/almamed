<?php
class shopSettingsShippingSortController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        $id = waRequest::post('module_id', 0, waRequest::TYPE_INT);
        $after_id = waRequest::post('after_id', 0, waRequest::TYPE_INT);

        $userlog = shopUserlogPlugin::getInstance();
        $order_before = $userlog ? shopUserlogSettingsSnapshot::capturePluginsOrder(shopPluginModel::TYPE_SHIPPING) : null;

        $model = new shopPluginModel();
        try {
            $model->move($id, $after_id, shopPluginModel::TYPE_SHIPPING);
            if ($userlog && $order_before !== null) {
                $userlog->logSettingsChange(
                    'Доставка: порядок',
                    $order_before,
                    shopUserlogSettingsSnapshot::capturePluginsOrder(shopPluginModel::TYPE_SHIPPING)
                );
            }
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }
}
