<?php

$model = new waModel();

try {
    $model->query('SELECT * FROM shop_carts_plugin_contact WHERE 0');
} catch (waDbException $e) {
    $sql = 'CREATE TABLE IF NOT EXISTS `shop_carts_plugin_contact` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `code` varchar(32) NOT NULL default "",
  `contact` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;';
    $model->exec($sql);
}