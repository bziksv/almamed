<?php

$model = new waModel();

$columns = array(
    'sales_manager' => "ALTER TABLE `shop_slider` ADD `sales_manager` varchar(255) NOT NULL DEFAULT ''",
    'content_manager' => "ALTER TABLE `shop_slider` ADD `content_manager` varchar(255) NOT NULL DEFAULT ''",
    'views_count' => "ALTER TABLE `shop_slider` ADD `views_count` int(11) NOT NULL DEFAULT '0'",
    'clicks_count' => "ALTER TABLE `shop_slider` ADD `clicks_count` int(11) NOT NULL DEFAULT '0'",
);

foreach ($columns as $sql) {
    try {
        $model->exec($sql);
    } catch (Exception $e) {
    }
}
