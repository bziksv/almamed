<?php

class shopCartsPluginBackendMenuController extends waJsonController
{
    public function execute()
    {
        $model = new shopCartsPluginMessageModel();
        $this->response = $model->getMenu();
    }
}
