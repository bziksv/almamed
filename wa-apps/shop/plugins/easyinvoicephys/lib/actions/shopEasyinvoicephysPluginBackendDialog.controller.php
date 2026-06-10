<?php
/*
//Сopyright "WA-Master ©"
//Plugin for framework "Webasyst Shop Script"
//The author of the plugin 'Snooper'- "snooper@ylig.ru"
*/
class shopEasyinvoicephysPluginBackendDialogController extends waJsonController {
	
    private static $plugin;

    private static function getPlugin() {
        if (!empty(self::$plugin)) {
            $plugin = self::$plugin;
        } else {
            $plugin = wa()->getPlugin('easyinvoicephys');
        }
        return $plugin;
    }

	
    public function execute() {
		
		$wf=new shopWorkflow();		
		$plugin = self::getPlugin();
		$plugin_id = array('shop', 'easyinvoicephys');
        $app_settings_model = new waAppSettingsModel() ;
		$settings = $plugin->getSettings();
        $set = $app_settings_model->get(array('shop', 'easyinvoicephys')) ;
		$settings['limit']  = !empty($set['limit']) ? $set['limit'] : 100 ;
        $output = $this->getOrders(0, $settings['limit']);				
		$state_names = array();
        foreach ($wf->getAvailableStates() as $state_id => $state) {
            $state_names[$state_id] = $state['name'];
        }
		$view = wa()->getView();
        $view->assign('orders', $output['orders']);
        $view->assign('states', $output['states']);
		$view->assign('plugin_name', $plugin->getName());
        $view->assign('plugin_id', $plugin_id[1]);
        $view->assign('state_names', $state_names);
		$view->assign('settings', $settings);			
        $html = $view->fetch('plugins/easyinvoicephys/templates/EasyinvoicephysPopup.html');
        $this->response = $html;   
    }

    public function getOrders($offset = 0, $limit = 100) {        
        
        $output = array();
        $wf = new shopWorkflow();
		$settings['states'] =  array_keys($wf->getAvailableStates());
        $completedArray = array(
                1=> 'completed',
                2=> 'refunded',
                3=> 'deleted'
            );
        $newOrders = array_diff( $settings['states'], $completedArray);
        $output['states'] = $newOrders;
        $model = new shopOrderModel();        
        $orders = $model->getList("*,items.name,items.type,items.quantity,contact,params", array(
            'offset' => $offset,
            'limit' => $limit,
           )
        );
        shopHelper::workupOrders($orders);        
        $output['orders'] =  $orders;        
        return $output;
    }
}