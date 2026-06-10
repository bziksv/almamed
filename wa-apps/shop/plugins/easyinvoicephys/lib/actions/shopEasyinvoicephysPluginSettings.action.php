<?php

class shopEasyinvoicephysPluginSettingsAction extends waViewAction
{
	private static $plugin;

    private static function getPlugin()
    {
        if (!empty(self::$plugin)) {
            $plugin = self::$plugin;
        } else {
            $plugin = wa()->getPlugin('easyinvoicephys');
        }
        return $plugin;
    }
	
	public function execute()
    {
		$wf=new shopWorkflow();	
		$key = array('shop', 'easyinvoicephys');
		$plugin_shop = 'shop_easyinvoicephys';
		$plugin_id = 'easyinvoicephys';
		$app_settings_model = new waAppSettingsModel();		
		$plugin = self::getPlugin();		
		$view = wa()->getView();
		
		$state_names = array();
        foreach ($wf->getAvailableStates() as $state_id => $state) {
            $state_names[$state_id] = $state['name'];
        }
	$contactfield = array(
		'BUYER_JURNAME_2',
		'BUYER_JURADDRESS_2',
		'BUYER_INN_2',
		'BUYER_KPP_2',
		'BUYER_RS_2',
		'BUYER_BANK_2',
		'BUYER_KS_2',
		'BUYER_BIK_2',
		'BUYER_DOP1',
		'BUYER_DOP2',
		'BUYER_DOP3',
		'BUYER_DOP4',
		'BUYER_DOP5',
		'BUYER_DOP6',
		'BUYER_ADDR1',
		'BUYER_ADDR2',
		'BUYER_ADDR3',
		'BUYER_ADDR4',
		'PREPAY'
	);
	foreach ($contactfield as $name) {
		$cf = waHtmlControl::getControl(waHtmlControl::CONTACTFIELD, $name, array(
			'namespace'	=> $plugin_shop,
			'value' 	=> $app_settings_model->get($key, $name),
		));			
		$this->view->assign($name, $cf);
	}
		
	$settings = array(
		'BUYER_JURNAME_NAME',
		'BUYER_JURADDRESS_NAME',
		'BUYER_INN_NAME',
		'BUYER_KPP_NAME',
		'BUYER_RS_NAME',
		'BUYER_BANK_NAME',
		'BUYER_KS_NAME',
		'BUYER_BIK_NAME',
		'BUYER_JURNAME',
		'BUYER_JURADDRESS',
		'BUYER_INN',	
		'BUYER_KPP',
		'BUYER_RS',
		'BUYER_BANK',
		'BUYER_KS',
		'BUYER_BIK',
		'user_css',
		'resettpl',
		'resetcsstpl',
		'faximile_width',
		'faximile_height',
		'faximile_x',
		'faximile_y',
		'stamp_width',
		'stamp_height',
		'stamp_x',
		'stamp_y',
		'COMPANY_NAME_1',
		'COMPANY_BOSS',
		'COMPANY_BUH',
		'COMPANY_ADDRESS',
		'COMPANY_BANK_NUMBER',
		'COMPANY_BANK_NAME',
		'COMPANY_INN',
		'COMPANY_KPP',
		'COMPANY_KORR',
		'COMPANY_BIK',
		'COMPANY_MAN',
		'COMPANY_MAN_2',
		'COMPANY_MAN_3',
		'COMPANY_MAN_4',
		'COMPANY_MAN_5',
		'COMPANY_PHONE1',
		'COMPANY_EMAIL1',
		'PREPAY_SYS',
		'BUYER_FIO',
		'BUYER_DOP1_NAME',
		'BUYER_DOP2_NAME',
		'BUYER_DOP3_NAME',
		'BUYER_DOP4_NAME',
		'BUYER_DOP5_NAME',
		'BUYER_DOP6_NAME',
		'BUYER_ADDR1_NAME',
		'BUYER_ADDR2_NAME',
		'BUYER_ADDR3_NAME',
		'BUYER_ADDR4_NAME',
		'TITLE_PAGE',
		'BUTTON_NAME',
		'FONT_SIZE',
		'TOP_SIZE_LIST',
		'BASEHYS',
		'TAX',		
		'TAX_TEXT',
		'ZERO_TAX',
		'ORDER_NO',
		'PRINT_FORMAT',
		'DISCOUNT',
		'SPEED_PRINT',
		'SKU',
		'WORDTEXT',
		'WORDTEXT_POS',
		'PAYMENTSEE',
		'SHIPPING',
		'DATE_TIME',
		'BUTTON_POSITION',
		'DEFAULT_ORDER_STATUS',
		'COLOR_SCHEME1',
		'COLOR_SCHEME2',
		'limit',
		'resettpl',
		'resetcsstpl'
	);
	foreach ($settings as $val) {
		$settings[$val] = $app_settings_model->get($key, $val);	
	}
	
	$images = array('logo', 'quarantee', 'faximile', 'stamp');
		foreach ($images as $img) {
			$settings[$img.'_src'] = shopEasyinvoicephysPlugin::fileSrc($app_settings_model->get($key, $img.'_image'));
		}
		
		$this->view->assign('getUsers', shopEasyinvoicephysPlugin::getUsers());
        $this->view->assign('plugin_id', $plugin_id);
        $this->view->assign('plugin_shop', $plugin_shop);
		$this->view->assign('plugin_ver', $plugin->getVersion());
        $this->view->assign('plugin_path', $plugin->getPluginStaticUrl());
		$this->view->assign('settings', $settings);
        $this->view->assign('state_names', $state_names);  
		/*------------------------------------------------------------------------*/
		$template = shopEasyinvoicephysPlugin::getPageTemplateControl($app_settings_model->get($key, 'template'));
		$css = shopEasyinvoicephysPlugin::getCssTemplateControl($app_settings_model->get($key, 'css'));
    }
}

//EOF