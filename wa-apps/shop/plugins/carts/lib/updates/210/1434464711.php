<?php

$model = new waModel();
try {
    $model->query('SELECT sender_name FROM shop_carts_plugin_message WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE shop_carts_plugin_message ADD sender_name VARCHAR(255) NULL DEFAULT NULL AFTER sender;');
}
