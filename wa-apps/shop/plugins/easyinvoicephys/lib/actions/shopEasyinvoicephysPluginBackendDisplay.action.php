<?php
/*
//Сopyright "WA-Master ©"
//Plugin for framework "Webasyst Shop Script"
//The author of the plugin 'Snooper'- "snooper@ylig.ru"
*/
class shopEasyinvoicephysPluginBackendDisplayAction extends waViewAction {
	
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
        $plugin = self::getPlugin();

        $order_id = waRequest::request('order_id', null, waRequest::TYPE_INT);
        $order = shopPayment::getOrderData($order_id);
        $settings = $plugin->getSettings();

        switch (wa()->getEnv()) {
            case 'backend':
                if (!$order && !$order_id) {
                    $dummy_order = array(
                        'contact_id' => $this->getUser()->getId(),
                        'id'         => 1,
                        'id_str'     => shopHelper::encodeOrderId(1),
                        'currency'   => $this->allowedCurrency(),
                    );
                    $order = waOrder::factory($dummy_order);
                } elseif (!$order) {
                    throw new waException('Order not found', 404);
                }
                break;
            default:
                if (!$order) {
                    throw new waException('Order not found', 404);
                }
                break;
        }

        if ($order && $order->items) {
            $items = $this->getItems($order);
        } else {
            $items = array();
        }

        $formatter = new waContactAddressSeveralLinesFormatter();
        $shipping_address = array();
        foreach (waContactFields::get('address')->getFields() as $k => $v) {
            if (isset($order->params['shipping_address.'.$k])) {
                $shipping_address[$k] = $order->params['shipping_address.'.$k];
            }
        }

        $shipping_address_text = array();
        foreach (array('country_name', 'region_name', 'zip', 'city', 'street') as $k) {
            if (isset($order->shipping_address[$k])) {
                $shipping_address_text[] = $order->shipping_address[$k];
            }
        }
		
		$plugin_id = array('shop', 'easyinvoicephys');
		$app_settings_model = new waAppSettingsModel();
		$settings['faximile_src'] = shopEasyinvoicephysPlugin::fileSrc($app_settings_model->get($plugin_id, 'faximile_image'));			
		$settings['stamp_src'] = shopEasyinvoicephysPlugin::fileSrc($app_settings_model->get($plugin_id, 'stamp_image'));		
		//----------------------------------------------------------------------------------------
		
        $shipping_address_text = implode(', ', $shipping_address_text);
        $this->view->assign('shipping_address_text', $shipping_address_text);
        $shipping_address = $formatter->format(array('data' => $shipping_address));
        $shipping_address = $shipping_address['value'];
        $this->view->assign('shipping_address', $shipping_address);
        $this->view->assign('settings', $settings);
        $this->view->assign('order', $order);
        $this->view->assign('items', $items);
        $this->view->assign('plugin_path', $plugin->getPluginStaticUrl());
		//----------------------------------------------------------------------------------------
        $this->view->assign('plugin_ver', $plugin->getVersion());
        $this->view->assign('plugin_name', $plugin->getName());
		$this->view->assign('plugin_id', $plugin_id[1]);
		$this->view->assign('plugin', $plugin);
		$css = shopEasyinvoicephysPlugin::getCssTemplateControl($app_settings_model->get($plugin_id, 'css'));
		
        $template_path = wa()->getDataPath('plugins/easyinvoicephys/templates/EasyinvoicephysPage.html', false, 'shop', true);
        if (!file_exists($template_path)) {
            $template_path = wa()->getAppPath('plugins/easyinvoicephys/templates/EasyinvoicephysPage.html', 'shop');
		}
		
        $this->setTemplate($template_path);
    }

     private function getItems($order) {
		
        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();		
        $items = $order->items;
        $product_model = new shopProductModel();
        $tax = 0;
        foreach ($items as & $item) {
            $data = $product_model->getById($item['product_id']);
            $item['tax_id'] = ifset($data['tax_id']);
            $item['currency'] = $order->currency;
        }

        unset($item);
        shopTaxes::apply($items, array(
            'billing'  => $order->billing_address,
            'shipping' => $order->shipping_address,
        ), $order->currency);
		
		if ($order->discount) {
			if ($order->total + $order->discount - $order->shipping > 0) {
                $k = 1.0 - ($order->discount) / ($order->total + $order->discount - $order->shipping);
            } else {
                $k = 0;
            }
			if ($order->shipping > 0 && isset($settings['SHIPPING']) && $settings['SHIPPING'] == 4) {
				$k = $k + ($order->shipping) / ($order->total + $order->discount - $order->shipping);
			}
            foreach ($items as & $item) {
                if ($item['tax_included']) {
                    $item['tax'] = round($k * $item['tax'], 4);
                }
                $item['price'] = round($k * $item['price'], 4);
                $item['total'] = round($k * $item['total'], 4);
            }			
            unset($item);
        }		
        return $items;
    }
}
