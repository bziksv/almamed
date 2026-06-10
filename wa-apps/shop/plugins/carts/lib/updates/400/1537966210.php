<?php


try {
    $m = new waModel();
    $m->query('ALTER TABLE `shop_carts_plugin_storefront` CHANGE `total` `total` DECIMAL(12,4) NOT NULL DEFAULT \'0.0000\';');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}
