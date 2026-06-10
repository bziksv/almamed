<?php

$model = new waModel();

$model->exec("CREATE TABLE IF NOT EXISTS `shop_wholesale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku_id` int(11) NOT NULL,
  `min_sku_count` int(11) NOT NULL,
  `multiplicity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `sku_id` (`sku_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8");