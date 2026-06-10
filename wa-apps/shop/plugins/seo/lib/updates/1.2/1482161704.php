<?php

$m = new waModel();

$m->query(
	'CREATE TABLE IF NOT EXISTS `shop_seo_settings_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
);