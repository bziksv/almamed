<?php

class shopRelatedPluginBackendSaveController extends waJsonController
{
    /**
     * @var waView $view
     */
    private $view;

    /**
     * @var shopVendorlinkPlugin $plugin
     */
    private $plugin;


    private $path;

    function __construct()
    {
        $this->view = waSystem::getInstance()->getView();
        $this->plugin = wa('shop')->getPlugin('related');
        $this->path = wa()->getAppPath('plugins/related', 'shop');
    }

    public function execute()
    {
        $product_id = waRequest::post('product_id', 0, 'int');
        $selected = waRequest::post('selected');

        if($product_id || $selected)
            $this->plugin->saveSettings(['view_'.$product_id => $selected]);
    }
}
