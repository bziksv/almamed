<?php

class shopBreadcrumbsTableMigration
{
	public function toUtf8Mb4(waModel $model)
	{
		$sql = '
ALTER TABLE `' . $model->getTableName() . '`
	COLLATE=\'utf8mb4_general_ci\',
	CONVERT TO CHARSET utf8mb4;
';

		$model->exec($sql);
	}

	public function toUtf8(waModel $model)
	{
		$sql = '
ALTER TABLE `' . $model->getTableName() . '`
	COLLATE=\'utf8_general_ci\',
	CONVERT TO CHARSET utf8;
';

		$model->exec($sql);
	}
}