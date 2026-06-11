<?php

$model = new waModel();

$columns = array(
    'alt' => "ALTER TABLE `shop_slider` ADD `alt` varchar(512) NOT NULL DEFAULT ''",
    'img_tablet' => "ALTER TABLE `shop_slider` ADD `img_tablet` varchar(512) NOT NULL DEFAULT ''",
    'img_mobile' => "ALTER TABLE `shop_slider` ADD `img_mobile` varchar(512) NOT NULL DEFAULT ''",
);

foreach ($columns as $sql) {
    try {
        $model->exec($sql);
    } catch (Exception $e) {
    }
}
