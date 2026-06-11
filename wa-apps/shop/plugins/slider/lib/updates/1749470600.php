<?php

$model = new waModel();

$columns = array(
    'sales_manager_id' => "ALTER TABLE `shop_slider` ADD `sales_manager_id` int(11) NULL DEFAULT NULL",
    'content_manager_id' => "ALTER TABLE `shop_slider` ADD `content_manager_id` int(11) NULL DEFAULT NULL",
);

foreach ($columns as $sql) {
    try {
        $model->exec($sql);
    } catch (Exception $e) {
    }
}
