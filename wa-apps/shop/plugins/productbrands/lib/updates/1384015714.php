<?php

$model = new waModel();

try {
    $model->query('SELECT seo_description FROM shop_productbrands WHERE 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD seo_description TEXT NULL");
}