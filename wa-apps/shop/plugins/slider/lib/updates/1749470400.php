<?php

$model = new waModel();

$columns = array(
    'enabled' => "ALTER TABLE `shop_slider` ADD `enabled` tinyint(1) NOT NULL DEFAULT '1'",
    'date_from' => "ALTER TABLE `shop_slider` ADD `date_from` date NULL DEFAULT NULL",
    'date_to' => "ALTER TABLE `shop_slider` ADD `date_to` date NULL DEFAULT NULL",
);

foreach ($columns as $sql) {
    try {
        $model->exec($sql);
    } catch (Exception $e) {
    }
}
