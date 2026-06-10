<?php

$model = new waModel();
try {
    $model->query('SELECT total FROM shop_carts_plugin_storefront WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE shop_carts_plugin_storefront ADD total DECIMAL( 10, 4 ) NOT NULL DEFAULT  0 AFTER contact_id;');
    $model->exec('ALTER TABLE shop_carts_plugin_storefront ADD currency VARCHAR( 3 ) NOT NULL DEFAULT  \'USD\' AFTER total;');
}
