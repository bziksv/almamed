<?php

waAppConfig::clearAutoloadCache('shop');

$model = new shopSearchproSettingsModel();
foreach (array(
	'category_filter_status' => '0',
	'dropdown_categories_status' => '0',
) as $name => $value) {
	if (!$model->getByField('name', $name)) {
		$model->insert(array('name' => $name, 'value' => $value));
	} else {
		$model->updateByField(array('name' => $name), array('value' => $value));
	}
}

$cache = new waVarExportCache('app_settings/shop.searchpro', 86400, 'webasyst');
$cache->delete();
