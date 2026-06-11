<?php

$model = new waModel();

try {
	$model->exec(
		'ALTER TABLE `shop_searchpro_query` ADD INDEX `idx_status_frequency` (`status`, `frequency`)'
	);
} catch (waDbException $e) {
}

try {
	$model->exec(
		'ALTER TABLE `shop_searchpro_query` ADD INDEX `idx_last_datetime` (`last_datetime`)'
	);
} catch (waDbException $e) {
}
