<?php

class shopProducthiddenPluginSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $code = waRequest::post('code');
        $value = waRequest::post('value');

        $productFeatureModel = new shopProductParamsModel();
        $p = new shopProduct($id);

        $arParams = $productFeatureModel->getData($p);

        $arParams[$code] = $value;
        $productFeatureModel->setData($p, $arParams);
    }
}
