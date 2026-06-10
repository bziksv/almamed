<?php

$model = new waModel();
try {
    $model->query('SELECT manager FROM shop_product WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE shop_product ADD manager INT(11) UNSIGNED NULL DEFAULT NULL');
}