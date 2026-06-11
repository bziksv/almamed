<?php

class shopSearchproPluginRebuildGramsCli extends waCliController
{
	const BATCH_SIZE = 500;

	const TYPES = array('products', 'categories');

	public function execute()
	{
		$clear = waRequest::param('clear', '1') !== '0';
		$types = waRequest::param('types', '');
		$type_list = self::TYPES;

		if ($types !== '') {
			$type_list = array();
			foreach (explode(',', $types) as $type) {
				$type = trim($type);
				if (in_array($type, self::TYPES, true)) {
					$type_list[] = $type;
				}
			}
			if (!$type_list) {
				$type_list = self::TYPES;
			}
		}

		$grams_model = new shopSearchproGramsModel();
		if ($clear) {
			echo "Clearing grams index...\n";
			$grams_model->clearGrams();
		}

		$total = 0;
		$processed = 0;

		foreach ($type_list as $type) {
			$updater = new shopSearchproGramsUpdater($type);
			$total += (int) $updater->getEntityCount();
		}

		echo "Entities to process: {$total}\n";

		foreach ($type_list as $type) {
			$updater = new shopSearchproGramsUpdater($type);
			$type_total = (int) $updater->getEntityCount();
			$offset = 0;

			echo "Type {$type}: {$type_total}\n";

			while ($offset < $type_total) {
				$batch = $updater->update($offset, self::BATCH_SIZE);
				if ($batch <= 0) {
					break;
				}
				$offset += $batch;
				$processed += $batch;
				$percent = $total > 0 ? round($processed / $total * 100, 1) : 100;
				echo "[{$percent}%] {$processed}/{$total}\n";
			}
		}

		$counts = $grams_model->count();
		echo "Done. Grams total: " . (int) ifset($counts, 'all', 0) . "\n";
	}
}
