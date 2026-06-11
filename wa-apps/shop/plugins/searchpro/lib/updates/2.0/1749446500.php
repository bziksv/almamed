<?php

$model = new waModel();

try {
	$exists = $model->query(
		"SELECT name FROM shop_searchpro_settings WHERE name = 'use_v2'"
	)->fetchField();

	if (!$exists) {
		$model->exec(
			"INSERT INTO shop_searchpro_settings (name, value) VALUES ('use_v2', '1')"
		);
	}
} catch (waDbException $e) {
}

$cache = new waVarExportCache('app_settings/shop.searchpro', 86400, 'webasyst');
$cache->delete();

waAppConfig::clearAutoloadCache('shop');
