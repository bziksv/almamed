<?php

class shopProductbrandsPluginBackendAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('brands', shopProductbrandsPlugin::getBrands());
    }
}
