<?php

class shopDescriptionmanagerPluginFrontendGetController extends waJsonController
{
    /**
     * @var waView $view
     */
    private $view;

    /**
     * @var shopVendorlinkPlugin $plugin
     */
    private $plugin;

    function __construct()
    {
        $this->view = waSystem::getInstance()->getView();
        $this->plugin = wa()->getPlugin('descriptionmanager');
    }

    public function execute()
    {
        $product_id = waRequest::post('product_id', 0, 'int');
        if($product_id){

            $model = new shopDescriptionmanagerModel();
            $this->response = array(
                'request' => $model->getById($product_id),
            );
        }
    }
}
