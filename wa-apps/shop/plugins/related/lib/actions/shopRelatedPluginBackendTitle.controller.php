<?php

class shopRelatedPluginBackendTitleController extends waJsonController
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
        $type = waRequest::post('type');
        $title = waRequest::post('title');

        if($product_id || $type)
            $this->plugin->saveSettings(['title_'.$type.'_'.$product_id => $title]);
    }
}
