<?php
	$path = wa()->getConfig()->getAppConfig('shop');
	$path = $path->getPluginPath('easyinvoicephys');

	waFiles::delete($path."/templates/EasyinvoicephysFrontendButton.html", true);
	waFiles::delete($path."/lib/actions/shopEasyinvoicephysPluginFrontendDisplay.action.php", true);

	$key = array('shop', 'easyinvoicephys');
	$app_settings_model = new waAppSettingsModel();

	$settings = array(
		'limit' => '10'
	);
		foreach($settings as $k => $val)
			$app_settings_model->set($key, $k, $val);