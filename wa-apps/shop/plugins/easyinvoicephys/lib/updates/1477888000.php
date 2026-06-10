<?php
	//---------delete---template---file--------------------//
	$template_path = wa()->getDataPath('plugins/easyinvoicephys/templates/EasyinvoicephysPage.html', false, 'shop', true);
	waFiles::delete($template_path, true);
	$css_path = wa()->getDataPath('plugins/easyinvoicephys/css/easyinvoicephys.css', false, 'shop', true);
	waFiles::delete($css_path, true);
	
	$key = array('shop', 'easyinvoicephys');
	$app_settings_model = new waAppSettingsModel();

	$settings = array(
		'resettpl' => '1',
		'resetcsstpl' => '1'
	);
		foreach($settings as $k => $val)
			$app_settings_model->set($key, $k, $val);