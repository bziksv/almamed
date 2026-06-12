<?php

// Theme already loads Google Roboto — skip duplicate Searchpro-Roboto woff2 (~130 KB).
$model = new shopSearchproThemeSettingsModel();
foreach ($model->query('SELECT DISTINCT theme_id FROM shop_searchpro_theme_settings') as $row) {
	$theme_id = ifset($row, 'theme_id', '');
	if ($theme_id === '') {
		continue;
	}
	$model->exec(
		'INSERT INTO shop_searchpro_theme_settings (`theme_id`, `name`, `value`)
		 VALUES (s:theme_id, s:name, s:value)
		 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
		array(
			'theme_id' => $theme_id,
			'name' => 'design_custom_fonts_status',
			'value' => '0',
		)
	);
}
