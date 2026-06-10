<?php

class shopVendorlinkPlugin extends shopPlugin
{
    /**
     * @var waView $view
     */
    private static $view;
    private static function getView()
    {
        if (!isset(self::$view)) {
            self::$view = waSystem::getInstance()->getView();
        }
        return self::$view;
    }

    /**
     * @var shopVendorlinkPlugin $plugin
     */
    private static $plugin;
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa()->getPlugin('vendorlink');
        }
        return self::$plugin;
    }

    /**
     * @var shopProduct $product
     */
    public function backendProduct($product) {
        $view = self::getView();
        $view->assign('product_id', $product['id']);

        $lm = new shopVendorlinkLinksModel();
        $links = $lm->getLinksByProductId($product['id']);
        $view->assign('links', $links);

        return array(
            'toolbar_section' => $view->fetch($this->path . '/templates/toolbar.html'),
            'edit_basics' => $view->fetch($this->path . '/templates/toolbar.edit.html'),
        );
    }

    public function backendOrder($order) {
        $view = self::getView();
        $lm = new shopVendorlinkLinksModel();
        $items = array();
        foreach($order['items'] as $item) {
            $links = $lm->getLinksByProductId($item['product_id']);
            $items[$item['id']]['links'] = $links;
        }
        $view->assign('items', json_encode($items));
        return array(
            'info_section' => $view->fetch($this->path . '/templates/order_links.html'),
        );
    }

    public function getPluginPath() {
        return $this->path;
    }

    public function productDelete($params)
    {
        $lm = new shopVendorlinkLinksModel();
        $lm->deleteByField('product_id', $params['ids']);
    }

    public static function getUserCategories()
    {
        $ccm = new waContactCategoryModel();
        $categories = $ccm->getAll();
        $options = array(
            array(
                'title' => _wp('Admin'),
                'value' => 'admin',
            ),
            array(
                'title' => _wp('Unregistered'),
                'value' => 'unregistered',
            ),
            array(
                'title' => _wp('Without category'),
                'value' => 'uncategorized',
            ),
        );
        foreach ($categories as $category) {
            $option = array(
                array(
                    'title' => $category['name'],
                    'value' => 'id-'.$category['id'],
                )
            );
            $options = array_merge($options, $option);
        }
        return $options;
    }

    public function frontendProduct($product) {
        $plugin = self::getPlugin();
        $view = self::getView();
        $settings = $plugin->getSettings();
        $user = wa()->getUser();
        $cc = new waContactCategoriesModel();

        $enable = false;

        if (wa()->getUser()->getRights('shop', 'products') && isset($settings['categories']['admin']) )  {
            $enable = true;
        }

        if (!$user->getId() && isset($settings['categories']['unregistered'])) {
            $enable = true;
        }
        if ($user->getId()) {
            $categories = $cc->getContactCategories($user->getId());
            if (empty($categories) && isset($settings['categories']['uncategorized'])) {
                $enable = true;
            }

            foreach ($categories as $category) {
                if (isset($settings['categories']['id-'.$category['id']])) {
                    $enable = true;
                }
            }
        }

        if ($enable) {
            $lm = new shopVendorlinkLinksModel();
            $links = $lm->getLinksByProductId($product['id']);
            $view->assign('links', $links);

            return array(
                'block' => $view->fetch($this->path . '/templates/product.html'),
            );
        }
        else return false;
    }
}



