<?php

$model = new waModel();

try {
    $model->query('SELECT * FROM shop_carts_plugin_message WHERE 0');
} catch (waDbException $e) {
    $sql = 'RENAME TABLE `shop_carts_plugin_email` TO `shop_carts_plugin_message`';
    $model->exec($sql);

    $sql = 'ALTER TABLE `shop_carts_plugin_message` ADD `name` VARCHAR(1000) NOT NULL DEFAULT "" AFTER `id`';
    $model->exec($sql);

    $sql = 'ALTER TABLE `shop_carts_plugin_message` ADD `type` TINYINT UNSIGNED NOT NULL DEFAULT "0" AFTER `delay`';
    $model->exec($sql);

    $sql = 'ALTER TABLE `shop_carts_plugin_message` ADD `body_sms` VARCHAR(1000) NOT NULL DEFAULT ""';
    $model->exec($sql);

    $sql = 'ALTER TABLE `shop_carts_plugin_message` ADD `status` TINYINT(1) NOT NULL DEFAULT "1"';
    $model->exec($sql);
}