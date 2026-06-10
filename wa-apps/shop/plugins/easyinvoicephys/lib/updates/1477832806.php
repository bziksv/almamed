<?php
	$path = wa()->getConfig()->getAppConfig('shop');
	$path = $path->getPluginPath('easyinvoicephys');

	waFiles::delete($path."/img/minus.png", true);
	waFiles::delete($path."/js/easyinvoicephys.js", true);
	
	//---------delete---template---file--------------------//
	$template_path = wa()->getDataPath('plugins/easyinvoicephys/templates/EasyinvoicephysPage.html', false, 'shop', true);
	waFiles::delete($template_path, true);
	$css_path = wa()->getDataPath('plugins/easyinvoicephys/css/easyinvoicephys.css', false, 'shop', true);
	waFiles::delete($css_path, true);
	
	$key = array('shop', 'easyinvoicephys');
	$app_settings_model = new waAppSettingsModel();

	$settings = array(
		'BUYER_BIK_NAME' => 'БИК: ',
		'BUYER_KS_NAME' => 'К/c: ',
		'BUYER_BANK_NAME' => 'банк: ',
		'BUYER_RS_NAME' => 'Р/с: ',
		'BUYER_KPP_NAME' => 'КПП: ',
		'BUYER_INN_NAME' => 'ИНН: ',
		'BUYER_JURADDRESS_NAME' => 'Юр. адрес: ',
		'BUYER_JURNAME_NAME' => 'Юр. название: ',
		'BUYER_JURADDRESS' => 'payment_params_juraddress',	
		'BUYER_INN' => 'payment_params_inn',	
		'BUYER_KPP' => 'payment_params_kpp',	
		'BUYER_RS' => 'payment_params_rs',	
		'BUYER_BANK' => 'payment_params_bank',	
		'BUYER_KS' => 'payment_params_ks',	
		'BUYER_BIK' => 'payment_params_bik',
		'BUYER_JURNAME_2' => 'company',
		'BUYER_JURADDRESS_2' => 'address',
		'BUYER_INN_2' => 'inn',
		'BUYER_KPP_2' => 'kpp',
		'BUYER_RS_2' => 'rs',
		'BUYER_BANK_2' => 'bank',
		'BUYER_KS_2' => 'ks',
		'BUYER_BIK_2' => 'bik',	
		'BUYER_DOP4_NAME' => '',
		'BUYER_DOP4' => '',
		'BUYER_DOP5_NAME' => '',
		'BUYER_DOP5' => '',
		'BUYER_DOP6_NAME' => '',
		'BUYER_DOP6' => '',
		'COLOR_SCHEME1' => '#eed',
		'COLOR_SCHEME2' => '#000',
		'BUTTON_POSITION' => '2',
		'DEFAULT_ORDER_STATUS' => 'all',
		'TAX_TEXT' => 'Без НДС',
		'FONT_SIZE' => '14px',
		'faximile_image' => '',
		'faximile_width' => '40',
		'faximile_height' => '',
		'faximile_x' => '200',
		'faximile_y' => '-115px',
		'TOP_SIZE_LIST' => '5mm',
		'stamp_image' => '',
		'stamp_width' => '35',
		'stamp_height' => '',
		'stamp_x' => '0',
		'stamp_y' => '-115px',
		'resettpl' => '1',
		'resetcsstpl' => '1'
	);
		foreach($settings as $k => $val)
			$app_settings_model->set($key, $k, $val);