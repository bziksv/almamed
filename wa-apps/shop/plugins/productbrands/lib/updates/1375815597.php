<?php

$model = new waModel();

try {
    $model->query('SELECT title FROM shop_productbrands WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD title VARCHAR(255) NULL DEFAULT NULL");
}

try {
    $model->query('SELECT meta_keywords FROM shop_productbrands WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD meta_keywords TEXT NULL");
}

try {
    $model->query('SELECT meta_description FROM shop_productbrands WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD meta_description TEXT NULL");
}