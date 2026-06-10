<?php

try {
    // old report file
    $file = wa('shop')->getAppPath('plugins/carts/lib/actions/backend/shopCartsPluginBackendReport.action.php');
    waFiles::delete($file);
    $file = wa('shop')->getAppPath('plugins/carts/templates/actions/backend/BackendReport.html');
    waFiles::delete($file);
}
catch (Exception $e)
{
    waLog::log('shop/plugins/carts: unable to delete old files "shopCartsPluginBackendReport.action.php" and "BackendReport.html".');
}

$model = new waModel();
try {
    $model->query('SELECT `contact_id` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_storefront` ADD  `contact_id` INT UNSIGNED NULL DEFAULT NULL;');
}

try {
    $model->query('SELECT `edit_datetime` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE `shop_carts_plugin_storefront` ADD  `edit_datetime` DATETIME NULL DEFAULT NULL;');
}

try {
    $model->query('SELECT `cancel` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_storefront` ADD  `cancel` BOOLEAN NOT NULL DEFAULT FALSE ;');
}

try {
    $model->query('SELECT `restore` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_storefront` ADD  `restore` BOOLEAN NOT NULL DEFAULT FALSE ;');
}

try {
    $model->query('SELECT `order_id` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_storefront` ADD  `order_id` INT UNSIGNED NULL DEFAULT NULL ;');
}

try {
    $model->query('SELECT `last_send_datetime` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_storefront` ADD  `last_send_datetime` DATETIME NULL DEFAULT NULL AFTER  `edit_datetime`;');
}