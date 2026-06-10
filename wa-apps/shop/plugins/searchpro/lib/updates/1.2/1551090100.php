<?php

$model = new waModel();

try {
	$model->exec("ALTER TABLE `shop_searchpro_query` ADD COLUMN `count` INT(11) NULL;");
} catch(waDbException $e) {}
