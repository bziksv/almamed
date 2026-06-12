<?php

waAppConfig::clearAutoloadCache('shop');

$storefront_model = new shopSearchproStorefrontSettingsModel();
$storefront_model->update('*', array(
	'dropdown_categories_status' => '1',
	'dropdown_categories_products_status' => '1',
	'dropdown_categories_min_count' => '1',
	'dropdown_categories_max_count' => '6',
	'dropdown_entities_sort' => array('categories', 'products', 'brands', 'popular', 'history'),
));

$cache = new waVarExportCache('app_settings/shop.searchpro', 86400, 'webasyst');
$cache->delete();
