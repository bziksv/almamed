<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM `shop_breadcrumbs_blog_settings` LIMIT 1');
}
catch (Exception $e)
{
	$sqls = array();

	$sqls[] = '
CREATE TABLE `shop_breadcrumbs_blog_settings` (
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`name`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$sqls[] = '
ALTER TABLE `shop_breadcrumbs_theme_settings`
	ADD COLUMN `app` VARCHAR(64) NOT NULL DEFAULT \'shop\' FIRST,
	DROP PRIMARY KEY;
';

	$sqls[] = '
ALTER TABLE `shop_breadcrumbs_theme_settings`
	CHANGE COLUMN `theme_id` `theme_id` CHAR(100) NOT NULL AFTER `app`
';

	$sqls[] = '
ALTER TABLE `shop_breadcrumbs_theme_settings`
	ADD PRIMARY KEY (`app`, `theme_id`, `name`);
';

	$sqls[] = '
ALTER TABLE `shop_breadcrumbs_theme_settings`
	ALTER `theme_id` DROP DEFAULT,
	ALTER `name` DROP DEFAULT;
ALTER TABLE `shop_breadcrumbs_theme_settings`
	CHANGE COLUMN `theme_id` `theme_id` VARCHAR(50) NOT NULL AFTER `app`,
	CHANGE COLUMN `name` `name` VARCHAR(50) NOT NULL AFTER `theme_id`;
';

	foreach ($sqls as $sql)
	{
		$model->exec($sql);
	}
}

$cleaner = new shopBreadcrumbsCleaner();
$cleaner->clean();