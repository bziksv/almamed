<?php

class shopEmptybrandPluginSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $code = waRequest::post('code');
        $value = waRequest::post('value');

        $productFeatureModel = new shopProductFeaturesModel();
        $p = new shopProduct($id);
        $productFeatureModel->setData($p, [$code => $value]);
    }
}
