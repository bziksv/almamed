<?php

$model = new waModel();

try {
    $model->query("SELECT `wholesale_min_product_count` FROM `shop_product` WHERE 0");
    $model->exec("ALTER TABLE `shop_product` DROP `wholesale_min_product_count`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `wholesale_multiplicity` FROM `shop_product` WHERE 0");
    $model->exec("ALTER TABLE `shop_product` DROP `wholesale_multiplicity`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `wholesale_min_sku_count` FROM `shop_product_skus` WHERE 0");
    $model->exec("ALTER TABLE `shop_product_skus` DROP `wholesale_min_sku_count`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `wholesale_sku_multiplicity` FROM `shop_product_skus` WHERE 0");
    $model->exec("ALTER TABLE `shop_product_skus` DROP `wholesale_sku_multiplicity`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `wholesale_min_sum` FROM `shop_category` WHERE 0");
    $model->exec("ALTER TABLE `shop_category` DROP `wholesale_min_sum`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `wholesale_min_product_count` FROM `wholesale_shop_category` WHERE 0");
    $model->exec("ALTER TABLE `shop_category` DROP `wholesale_min_product_count`");
} catch (waDbException $e) {
    
}
