<?php

class shopProductbrandsPluginBackendPagesAction extends waViewAction
{
    public function execute()
    {
        $brands_model = new shopProductbrandsModel();
        $brand = $brands_model->getBrand(waRequest::get('id'));
        $this->view->assign('brand', $brand);

    }
}
