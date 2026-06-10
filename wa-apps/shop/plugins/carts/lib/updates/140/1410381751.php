<?php

$model = new waModel();

try {
    $model->query('SELECT `code` FROM `shop_carts_plugin_storefront` WHERE 0');
} catch (waDbException $e) {
    $model->exec('CREATE TABLE IF NOT EXISTS `shop_carts_plugin_storefront` (
          `code` varchar(32) NOT NULL,
          `storefront` varchar(255) NOT NULL,
          PRIMARY KEY  (`code`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;'
    );
}