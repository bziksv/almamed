<?php

class shopSearchproPluginCleanupQueriesCli extends waCliController
{
	public function execute()
	{
		$top_limit = (int) waRequest::param('top', 10000);
		$keep_days = (int) waRequest::param('days', 90);
		$dry_run = (bool) waRequest::param('dry-run', false);

		if ($top_limit < 1) {
			$top_limit = 10000;
		}
		if ($keep_days < 1) {
			$keep_days = 90;
		}

		$model = new shopSearchproQueryModel();
		$before = (int) $model->query('SELECT COUNT(*) FROM shop_searchpro_query')->fetchField();

		if ($dry_run) {
			$would_delete = $model->countCleanupCandidates($top_limit, $keep_days);
			echo "Queries before: {$before}\n";
			echo "Would delete: {$would_delete}\n";
			echo "Would keep: " . ($before - $would_delete) . "\n";
			return;
		}

		$deleted = $model->cleanup($top_limit, $keep_days);
		$after = (int) $model->query('SELECT COUNT(*) FROM shop_searchpro_query')->fetchField();

		echo "Deleted: {$deleted}\n";
		echo "Before: {$before}, after: {$after}\n";
	}
}
