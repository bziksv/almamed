<?php

$model = new waModel();
try {
    $model->query('SELECT contact_id FROM shop_carts_plugin_contact WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE shop_carts_plugin_contact DROP id');
    $model->exec('ALTER TABLE shop_carts_plugin_contact DROP INDEX code');
    $model->exec('ALTER TABLE shop_carts_plugin_contact ADD PRIMARY KEY(code)');
    $model->exec('ALTER TABLE shop_carts_plugin_contact ADD contact_id INT UNSIGNED NULL DEFAULT NULL');
    
}
