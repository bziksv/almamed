<?php

$model = new waModel();

try {
    $model->query('SELECT `message_id` FROM `shop_carts_plugin_log` WHERE 0');
} catch (waDbException $e) {
    $queries = array(
        'ALTER TABLE `shop_carts_plugin_log` CHANGE `email_id` `message_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT "0"',
        'ALTER TABLE `shop_carts_plugin_log` ADD `email` VARCHAR( 255 ) NOT NULL DEFAULT "" AFTER `code`',
        'ALTER TABLE `shop_carts_plugin_log` ADD `phone` VARCHAR( 255 ) NOT NULL DEFAULT "" AFTER `email`',
        'ALTER TABLE `shop_carts_plugin_log` ADD `subject` TEXT AFTER  `phone`',
        'ALTER TABLE `shop_carts_plugin_log` ADD `body` TEXT AFTER  `subject`',
        'ALTER TABLE `shop_carts_plugin_log` ADD `body_sms` VARCHAR(1000) NOT NULL DEFAULT "" AFTER `body`',
    );
    foreach ($queries as $sql) {
        $model->exec($sql);
    }
}