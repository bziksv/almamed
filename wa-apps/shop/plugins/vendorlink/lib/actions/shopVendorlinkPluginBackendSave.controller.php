<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 7/23/15
 * Time: 10:44 PM
  */

class shopVendorlinkPluginBackendSaveController extends waJsonController
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
        $this->plugin = wa()->getPlugin('vendorlink');
    }

    public function execute()
    {
        $product_id = waRequest::post('product_id', 0, 'int');
        $links = waRequest::post('links');
        $product_model = new shopProductModel();
        $product = $product_model->getById($product_id);

        if (!empty($product)) {
            $lm = new shopVendorlinkLinksModel();
            foreach ($links as $link) {
                $data = array(
                    'product_id' => $product_id,
                    'link' => $link['text'],
                );
                if (isset($link['id'])) {
                    $old_link = $lm->getById($link['id']);
                    if (empty($link['text']) && !empty($old_link)) {
                        $lm->deleteById($old_link['id']);
                    }
                    elseif (!empty($link['text']) && !empty($old_link)) {
                        $lm->updateById($old_link['id'], $data);
                    }
                }
                else {
                    if (!empty($link['text'])) {
                        $lm->insert($data);
                    }
                }
            }
            $links = $lm->getLinksByProductId($product_id);
            $this->view->assign('links', $links);
            $this->view->assign('product_id', $product['id']);
            $this->response = array(
                'links_template' => $this->view->fetch($this->plugin->getPluginPath() . '/templates/links.html'),
            );
        }
        else {
            $this->setError(_wp('Product not found'));
        }

    }
}