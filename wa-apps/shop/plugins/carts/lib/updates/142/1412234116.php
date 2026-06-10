<?php

$model = new waModel();

try {
    $model->query('SELECT `sender` FROM `shop_carts_plugin_message` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_message` ADD  `sender` VARCHAR( 255 ) NOT NULL AFTER  `type`');
    $model->exec('ALTER TABLE  `shop_carts_plugin_message` ADD  `sender_sms` VARCHAR( 255 ) NOT NULL AFTER  `body`');
}