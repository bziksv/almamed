<?php

waAppConfig::clearAutoloadCache('shop');

$model = new shopSearchproSettingsModel();
if (!$model->getByField('name', 'category_filter_status')) {
	$model->insert(array(
		'name' => 'category_filter_status',
		'value' => '1',
	));
} else {
	$model->updateByField(array('name' => 'category_filter_status'), array('value' => '1'));
}

$cache = new waVarExportCache('app_settings/shop.searchpro', 86400, 'webasyst');
$cache->delete();
