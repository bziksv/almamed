<?php


class shopCartsPluginBackendTestAction extends waViewAction {

    public function execute()
    {
        $csm = new shopCartsPluginStorefrontModel();

        $sql = 'SELECT DISTINCT i.code FROM shop_cart_items i '.
            'JOIN shop_carts_plugin_storefront s ON s.code = i.code '.
            'WHERE i.type="product" ORDER BY i.create_datetime DESC '.
            'LIMIT 10';

        $codes = $csm->query($sql)->fetchAll(null,true);

        $carts = array();
        foreach($codes as $code) {

            $items = $csm->query('SELECT c.code, p.name, s.name sku, c.quantity FROM shop_cart_items c
                LEFT JOIN shop_product p ON p.id=c.product_id
                LEFT JOIN shop_product_skus s ON s.id=c.sku_id
                WHERE c.type="product" AND c.code = ?', $code)->fetchAll();

            $storefront = $csm->getStorefrontByCode($code);

            $carts[] = array(
                'code' => $code,
                'items' => $items,
                'storefront' => $storefront
            );
        }

        $this->view->assign('test_carts', $carts);
    }
}