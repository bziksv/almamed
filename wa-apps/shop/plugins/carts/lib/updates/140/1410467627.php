<?php

$model = new waModel();

try {
    $model->query('SELECT `source` FROM `shop_carts_plugin_message` WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE  `shop_carts_plugin_message` ADD  `source` VARCHAR( 255 ) NOT NULL DEFAULT "" AFTER  `delay`');
}