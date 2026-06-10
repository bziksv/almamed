<?php

$model = new waModel();

$model->query('CREATE TABLE IF NOT EXISTS `shop_seofilter_settings_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
