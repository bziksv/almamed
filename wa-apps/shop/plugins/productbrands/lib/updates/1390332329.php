<?php

$model = new waModel();

// url
try {
    $model->query("SELECT url FROM shop_productbrands WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD url VARCHAR (255) NULL");
}


// filter
try {
    $model->query("SELECT filter FROM shop_productbrands WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD filter TEXT NULL");
}


// hidden
try {
    $model->query("SELECT hidden FROM shop_productbrands WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productbrands ADD hidden TINYINT(1) NOT NULL DEFAULT 0");
}
