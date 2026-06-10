<?php

$model = new waModel();

try {
    $sql = 'SELECT `multiplicity` FROM `shop_product` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_product` ADD `multiplicity` INT NOT NULL AFTER `id`';
    $model->query($sql);
}

try {
    $sql = 'SELECT `min_sum` FROM `shop_category` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_category` ADD `min_sum` DECIMAL( 15, 4 ) NOT NULL AFTER `id`';
    $model->query($sql);
}

try {
    $sql = 'SELECT `min_product_count` FROM `shop_category` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_category` ADD `min_product_count` INT NOT NULL AFTER `id`';
    $model->query($sql);
}

$domains_settings = array();
if (method_exists('shopWholesale', 'saveDomainsSettings')) {
    shopWholesale::saveDomainsSettings($domains_settings);
}
