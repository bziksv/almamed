<?php

class shopCategoryfinderPluginUpdateController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $without_prod = filter_var(waRequest::post('without_prod'), FILTER_VALIDATE_BOOLEAN);

        if (!$id) {
            throw new waException('Category ID required');
        }

        (new shopCategoryfinderService())->setWithoutProd($id, $without_prod);

        $this->response = array(
            'status' => 'ok',
        );
    }
}
