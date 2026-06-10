<?php

$model = new waModel();

try {
    $sql = 'SELECT `wholesale_min_sku_count` FROM `shop_product_skus` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `shop_product_skus` ADD `wholesale_min_sku_count` INT NOT NULL DEFAULT '0' AFTER `id`";
    $model->query($sql);
}

try {
    $sql = 'SELECT `wholesale_sku_multiplicity` FROM `shop_product_skus` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `shop_product_skus` ADD `wholesale_sku_multiplicity` INT NOT NULL DEFAULT '0' AFTER `id`";
    $model->query($sql);
}

try {
    $sql = 'SELECT * FROM `shop_wholesale`';
    $wholesale = $model->query($sql)->fetchAll();
    foreach ($wholesale as $item) {
        $sql = "UPDATE `shop_product_skus` SET `wholesale_sku_multiplicity` = '{$item['min_sku_count']}', `wholesale_min_sku_count` = '{$item['multiplicity']}' WHERE `id` = '{$item['sku_id']}';";
        $model->query($sql);
    }
} catch (waDbException $ex) {
    
}

try {
    $sql = 'DROP TABLE `shop_wholesale`';
    $model->query($sql);
} catch (waDbException $ex) {
    
}

try {
    $files = array(
        'plugins/wholesale/lib/config/db.php',
        'plugins/wholesale/lib/models/shopWholesalePlugin.model.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginBackend.action.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginBackendDelete.controller.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginBackendSave.controller.php',
        'plugins/wholesale/templates/actions/backend/Backend.html',
        'plugins/wholesale/templates/BackendProduct.html',
        'plugins/wholesale/js/loadcontent.js',
        'plugins/wholesale/js/wholesale.js',
        'plugins/wholesale/js/',
        'plugins/wholesale/lib/models/',
    );

    foreach ($files as $file) {
        waFiles::delete(wa()->getAppPath($file, 'shop'), true);
    }
} catch (Exception $e) {
    
}