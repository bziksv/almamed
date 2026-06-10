<?php


class shopRelatedPlugin extends shopPlugin
{
    /**
     * @var waSmarty3View|waView
     */
    protected $view;

    /**
     * @var shopDescriptionmanagerModel
     */
    protected $model;


    public function __construct($info)
    {
        parent::__construct($info);

        $this->view = $view = wa()->getView();

        $this->model = new shopRelatedModel();
    }


    public function backendRelated($product)
    {
        $related_model = new shopProductRelatedModel();
        $related = $related_model->getAllRelated($product->id);
        $related_value =  array_filter($related, function($k) {
            return preg_match('/user_value_related/', $k);
        }, ARRAY_FILTER_USE_KEY);

        $plugin = wa('shop')->getPlugin('related');
        $selectedView = $plugin->getSettings('view_'.$product->id);
        $optionView = [
            'thumbs' => 'Плитка',
            'list' => 'Список',
            'short-list' => 'Простой список'
        ];

        uksort($related_value, function($a, $b){
            $a = substr(strrchr($a, "_"), 1);
            $b = substr(strrchr($b, "_"), 1);
            return ($a > $b) ? 1 : -1;
        });

        $related_key = array_keys($related_value);
        foreach ($related_key as $type){
            $sort = substr(strrchr($type, "_"), 1);
            $title[$type] = ($plugin->getSettings('title_'.$type.'_'.$product->id)) ?: $product['title_name_related_'.$sort];
        }

        $this->view->assign('view', compact('selectedView', 'optionView'));
        $this->view->assign('title', $title);
        $this->view->assign('related_value', $related_value);

        return [
            'related'   => $this->view->fetch($this->path.'/templates/backend.html'),
        ];
    }

    public static function frontendRelated($id)
    {
        $related_model = new shopProductRelatedModel();
        $related = $related_model->getAllRelated($id);
        $related_value =  array_filter($related, function($k) {
            return preg_match('/user_value_related/', $k);
        }, ARRAY_FILTER_USE_KEY);

        uksort($related_value, function($a, $b){
            $a = substr(strrchr($a, "_"), 1);
            $b = substr(strrchr($b, "_"), 1);
            return ($a > $b) ? 1 : -1;
        });

        $related_key = array_keys($related_value);

        if(!$related_key)
            return false;

        $plugin = wa('shop')->getPlugin('related');

        foreach ($related_key as $type){

            $collection = new shopProductsCollection('related/'.$type.'/'.$id);
            if (!empty($collection)) {
                $sort = substr(strrchr($type, "_"), 1);
                $result['product']['item'][$sort] = $collection->getProducts('*', 100);
                $result['product']['title'][$sort] = $plugin->getSettings('title_'.$type.'_'.$id);
            }
        }

        $result['view'] = $plugin->getSettings('view_'.$id);

        return $result;
    }



}
