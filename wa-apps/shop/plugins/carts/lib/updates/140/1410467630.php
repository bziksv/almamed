<?php

$model = new waModel();

try {
    $model->query('ALTER TABLE shop_carts_plugin_log DROP INDEX code');
} catch (waDbException $e) {

}