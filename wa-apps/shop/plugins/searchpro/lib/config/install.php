<?php

try {
	$model = new waModel();
	$model->query("ALTER TABLE `shop_searchpro_grams` ENGINE=InnoDB;");
	$model->query("ALTER TABLE `shop_searchpro_grams` ADD FULLTEXT(`grams`)");
} catch(waDbException $e) {}