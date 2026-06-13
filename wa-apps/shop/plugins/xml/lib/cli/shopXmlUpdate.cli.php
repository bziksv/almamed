<?php

class shopXmlUpdateCli extends waCliController
{
	public function execute()
	{
		$plugin = wa('shop')->getPlugin('xml');
		$service = $plugin->getExportService();
		$settings = $plugin->getSettings();
		$cat_parent = $plugin->getFullTree(false, true, array(), true);
		$root = wa()->getConfig()->getPath('root') . DIRECTORY_SEPARATOR;

		if (!is_dir($root . 'bx-import')) {
			waFiles::create($root . 'bx-import');
		}

		foreach ($settings as $key => $set) {
			if (strpos($key, 'update_time') !== false) {
				continue;
			}

			if (strpos($key, 'category') === 0) {
				$exclude_ids = array_filter(array_map('intval', explode('_', $set)));
				$all_cat = array();
				foreach ($cat_parent as $cat) {
					if (!in_array((int) $cat['id'], $exclude_ids, true)) {
						$all_cat[] = (int) $cat['id'];
					}
				}
				$path = $root . 'bx-import/' . $key . '.xml';
				echo "Export categories profile {$key}...\n";
				$result = $service->runFullExport(array(
					'mode' => 'category',
					'category_roots' => $all_cat,
					'percent' => 0.1,
				), $path, function ($done, $total, $msg) {
					echo $msg . "\n";
				});
				echo "Done: {$result['products']} products -> {$path}\n";
			}

			if (strpos($key, 'brands') === 0) {
				$brand_ids = array_filter(array_map('intval', explode('_', $set)));
				$path = $root . 'bx-import/' . $key . '.xml';
				echo "Export brands profile {$key}...\n";
				$result = $service->runFullExport(array(
					'mode' => 'brand',
					'brand_ids' => $brand_ids,
					'percent' => 0.1,
				), $path, function ($done, $total, $msg) {
					echo $msg . "\n";
				});
				echo "Done: {$result['products']} products -> {$path}\n";
			}
		}
	}
}
