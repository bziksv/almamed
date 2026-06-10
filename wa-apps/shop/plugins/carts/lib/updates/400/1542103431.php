<?php

$m = new waModel();

try {
    $m->query('SELECT * FROM shop_carts_plugin_storefront_referer WHERE 0');
} catch (waDbException $e) {
    $m->query('CREATE TABLE shop_carts_plugin_storefront_referer (
  id int(10) UNSIGNED NOT NULL,
  storefront_id int(10) UNSIGNED NOT NULL,
  referer varchar(255) DEFAULT NULL,
  landing varchar(255) DEFAULT NULL,
  create_datetime datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8');

    $m->query('ALTER TABLE shop_carts_plugin_storefront_referer
  ADD PRIMARY KEY (id),
  ADD KEY storefront_id (storefront_id),
  ADD KEY create_datetime (create_datetime)');

    $m->query('ALTER TABLE shop_carts_plugin_storefront_referer
  MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT');
}