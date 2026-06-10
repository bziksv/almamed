<?php

class shopRelatedPluginBackendGetController extends waJsonController
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

        $related_model = new shopProductRelatedModel();
        $related = $related_model->getAllRelated($product_id);
        $related_value =  array_filter($related, function($k) {
            return preg_match('/user_value_related/', $k);
        }, ARRAY_FILTER_USE_KEY);

        if($related_value){

            foreach($related_value as $type => $value){
                $count_related[] = substr(strrchr($type, "_"), 1);
            }
            $current_count = max($count_related) + 1;
            $type = 'user_value_related_'.$current_count;

            $this->view->assign('type', $type);
        }else{
            $type = 'user_value_related_1';
        }

        $this->response = array(
            'type' => $type,
            'count' => ($current_count) ?: 1,
            'product_id' => $product_id
        );
    }
}
