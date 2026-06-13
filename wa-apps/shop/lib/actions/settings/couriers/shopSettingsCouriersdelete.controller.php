<?php

class shopSettingsCouriersdeleteController extends waJsonController
{
    public function execute()
    {
        $this->response = 'ok';

        $id = waRequest::post('id', '', 'int');
        if (!$id) {
            return;
        }
        $courier_model = new shopApiCourierModel();

        $courier = $courier_model->getById($id);
        if (!$courier) {
            return;
        }

        $userlog = shopUserlogPlugin::getInstance();
        $courier_before = $userlog ? shopUserlogSettingsSnapshot::captureCourier($id) : null;

        $courier_model->deleteById($id);
        $courier_storefronts_model = new shopApiCourierStorefrontsModel();
        $courier_storefronts_model->deleteByField('courier_id', $id);
        if ($courier['api_token']) {
            $token_model = new waApiTokensModel();
            $token_model->deleteByField('token', $courier['api_token']);
        }

        if ($userlog && $courier_before !== null) {
            $userlog->logSettingsChange(
                'Курьер: '.ifset($courier, 'name', '#'.$id),
                $courier_before,
                array()
            );
        }
    }
}

