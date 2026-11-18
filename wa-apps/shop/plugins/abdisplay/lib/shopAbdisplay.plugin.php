<?php

class shopAbdisplayPlugin extends shopPlugin
{
    /**
     * Theme / PHP: current variant code or null if experiment off.
     *
     * @param string $key
     * @return string|null
     */
    public static function variant($key)
    {
        return shopAbdisplayHelper::variant($key);
    }

    /**
     * @param string $key
     * @param string $event
     * @param array $meta
     * @return int|null
     */
    public static function track($key, $event, array $meta = array())
    {
        return shopAbdisplayHelper::track($key, $event, $meta);
    }

    /**
     * @event frontend_head
     * @return string
     */
    public function frontendHead()
    {
        $assignments = shopAbdisplayHelper::activeAssignments();
        if (!$assignments) {
            return '';
        }
        $view = wa()->getView();
        $view->assign(array(
            'abdisplay_assignments_json' => json_encode($assignments, JSON_UNESCAPED_UNICODE),
            'abdisplay_can_force'        => shopAbdisplayHelper::canForce() ? 1 : 0,
            'abdisplay_plugin_url'       => $this->getPluginStaticUrl(),
        ));
        return $view->fetch($this->path.'/templates/hooks/frontendHead.html');
    }

    /**
     * @event frontend_product
     * @param array $product
     * @return array
     */
    public function frontendProduct($product)
    {
        $code = shopAbdisplayHelper::variant('product_card');
        if ($code !== null) {
            shopAbdisplayHelper::track('product_card', shopAbdisplayHelper::EVENT_VIEW, array(
                'product_id' => (int) ifset($product, 'id', 0),
            ));
        }
        // No cart HTML injection; theme chooses include by variant().
        return array();
    }

    /**
     * @event cart_add
     * @param array $item
     */
    public function cartAdd($item)
    {
        shopAbdisplayHelper::trackCart(array(
            'product_id' => (int) ifset($item, 'product_id', 0),
            'sku_id'     => (int) ifset($item, 'sku_id', 0),
            'quantity'   => ifset($item, 'quantity', 1),
        ));
    }

    /**
     * @event order_action.create
     * @param array $params
     */
    public function orderActionCreate($params)
    {
        $order_id = (int) ifset($params, 'order_id', 0);
        shopAbdisplayHelper::trackOrder(array(
            'order_id' => $order_id,
        ));
    }

    /**
     * @event backend_menu
     * @return array
     */
    public function backendMenu()
    {
        $view = wa()->getView();
        $view->assign('plugin_url', $this->getPluginStaticUrl());
        return array(
            'core_li' => $view->fetch($this->path.'/templates/hooks/backendMenu.html'),
        );
    }
}
