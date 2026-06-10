<?php
return array(
	'shop_searchpro_grams' => array(
		'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
		'word' => array('varchar', 255, 'null' => 0),
		'grams' => array('text', 'null' => 0),
		'type' => array('varchar', 32, 'null' => 0, 'default' => 'general'),
		'frequency' => array('int', 11, 'null' => 0, 'default' => '0'),
		'subtype' => array('varchar', 32),
		':keys' => array(
			'PRIMARY' => 'id',
			'grams' => array('grams', 'fulltext' => 1),
		),
	),
	'shop_searchpro_query' => array(
		'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
		'status' => array('int', 1, 'null' => 0, 'default' => '1'),
		'first_datetime' => array('datetime'),
		'last_datetime' => array('datetime'),
		'query' => array('varchar', 255, 'null' => 0),
		'category_id' => array('int', 11, 'null' => 0, 'default' => '0'),
		'frequency' => array('int', 11, 'null' => 0, 'default' => '1'),
		'count' => array('int', 11),
		':keys' => array(
			'PRIMARY' => 'id',
		),
	),
	'shop_searchpro_settings' => array(
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => 'name',
		),
	),
	'shop_searchpro_storefront_groups' => array(
		'id' => array('int', 11, 'null' => 0),
		'storefronts' => array('text', 'null' => 0),
		':keys' => array(
		),
	),
	'shop_searchpro_storefront_settings' => array(
		'storefront_id' => array('varchar', 255, 'null' => 0),
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => array('storefront_id', 'name'),
		),
	),
	'shop_searchpro_theme_settings' => array(
		'theme_id' => array('varchar', 255, 'null' => 0),
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => array('theme_id', 'name'),
		),
	),
);
