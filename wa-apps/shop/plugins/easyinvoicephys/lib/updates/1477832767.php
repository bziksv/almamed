<?php
$key = array('shop', 'easyinvoicephys');
$app_settings_model = new waAppSettingsModel();

	$settings = array(
		'DATE_TIME' => '0',
		'resettpl' => '0',
		'resetcsstpl' => '0'
	);
		foreach($settings as $k => $val)
			$app_settings_model->set($key, $k, $val);