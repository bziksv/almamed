<?php

$m = new waModel();
try {
    $m->query('SELECT last_success_send_datetime FROM shop_carts_plugin_storefront WHERE 0');
} catch (Exception $e) {
    $sql = 'ALTER TABLE shop_carts_plugin_storefront ADD last_success_send_datetime DATETIME NULL DEFAULT NULL '.
        'AFTER last_send_datetime;';
    $m->query($sql);
}