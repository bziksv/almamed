<?php

$model = new waModel();
try {
    $model->query('DROP TABLE IF EXISTS shop_carts_plugin_contact');
    $model->query('DROP TABLE IF EXISTS shop_carts_plugin_log');
    $model->query('DROP TABLE IF EXISTS shop_carts_plugin_message');
    $model->query('DROP TABLE IF EXISTS shop_carts_plugin_storefront');
    $model->query('DROP TABLE IF EXISTS shop_carts_plugin_storefront_referer');
} catch(waDbException $e) {
    waLog::log('Unable to delete shop_carts_plugin tables from database.');
}