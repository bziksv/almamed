<?php

$model = new waModel();

try {
    $model->query("SELECT `min_product_count` FROM `shop_product` WHERE 0");
    $model->exec("ALTER TABLE `shop_product` CHANGE `min_product_count` `wholesale_min_product_count` INT( 11 ) NOT NULL");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `multiplicity` FROM `shop_product` WHERE 0");
    $model->exec("ALTER TABLE `shop_product` CHANGE `multiplicity` `wholesale_multiplicity` INT( 11 ) NOT NULL");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `min_sum` FROM `shop_category` WHERE 0");
    $model->exec("ALTER TABLE `shop_category` CHANGE `min_sum` `wholesale_min_sum` DECIMAL( 15, 4 ) NOT NULL");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `min_product_count` FROM `shop_category` WHERE 0");
    $model->exec("ALTER TABLE `shop_category` CHANGE `min_product_count` `wholesale_min_product_count` INT( 11 ) NOT NULL");
} catch (waDbException $e) {
    
}