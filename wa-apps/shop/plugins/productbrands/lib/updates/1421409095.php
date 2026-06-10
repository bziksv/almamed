<?php

$model = new waModel();
try {
    $model->query('SELECT params FROM shop_productbrands WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD params TEXT NULL");
}