<?php

class shopDescriptionmanagerPluginBackendSaveController extends waJsonController
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
        $data = waRequest::post();

        $product_model = new shopProductModel();
        $product = $product_model->getById($data['product_id']);

        if (!empty($product)) {
            $model = new shopDescriptionmanagerModel();
            $before_row = $model->getById($data['product_id']);

            if($model->countByField('product_id', $data['product_id']))
                $model->updateByField('product_id', $data['product_id'], $data);
            else
                $model->insert($data);

            $after_row = $model->getById($data['product_id']);
            if ($plugin = shopUserlogPlugin::getInstance()) {
                $plugin->logDescriptionmanagerChange($data['product_id'], $before_row ?: array(), $after_row ?: array());
            }
        }
        else {
            $this->setError(_wp('Product not found'));
        }

        $this->response = array(
            'request' => $data,
        );
    }
}
