<?php
class shopCurrencyratesPluginSaveselectedcurrenciesController extends waJsonController
{
    public function execute()
    {
        $currency = waRequest::post('currency');
        
        if (!is_array($currency)) {
            $currency = array();
        }
        
        $currency = json_encode($currency);
        
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set('shop.currencyrates', 'selected_currency_cron', $currency);
        
        $this->response['state'] = true;
    }
}