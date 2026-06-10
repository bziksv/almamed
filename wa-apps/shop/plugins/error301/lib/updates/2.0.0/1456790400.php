<?php
$model = new waModel();

try {
    $model->query('SELECT parent FROM shop_error301');
} catch (waDbException $e) {
    $model->exec('TRUNCATE TABLE `shop_error301`');
	$model->exec('ALTER TABLE `shop_error301`
					ADD COLUMN `parent` INT(11) NULL DEFAULT NULL AFTER `url`,
					DROP INDEX `unqkey`,
					ADD UNIQUE INDEX `unqkey` (`type`, `parent`, `url`);
					;');
}