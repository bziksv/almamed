<?php

$model = new waModel();
try {
    $model->query('SELECT h1 FROM shop_productbrands WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD h1 VARCHAR(255) NULL");
}