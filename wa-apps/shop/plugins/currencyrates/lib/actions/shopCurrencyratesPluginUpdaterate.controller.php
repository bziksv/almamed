<?php
class shopCurrencyratesPluginUpdaterateController extends waJsonController
{
    public function execute() {
        $code = waRequest::post('code', '', waRequest::TYPE_STRING);
        $rounding = waRequest::post('rounding', 2, waRequest::TYPE_INT);
        $rounding_side = waRequest::post('rounding_side', 2, waRequest::TYPE_INT);
        $multiple=waRequest::post('multiple');
        $summary = waRequest::post('summary');
        $multiple = (float)$multiple;
        $summary = (float)$summary;        
        $rate = waRequest::post('rate');
        
        $cur_model = new shopCurrencyModel();
        $rate_model = new shopCurrencyratesModel();
        
        $cur_model->changeRate($code, (float)$rate);
        $prim_cur = $cur_model->getPrimaryCurrency();
        $rate_model->updateByField('code',$code,array('rounding'=>$rounding,'rounding_side'=>$rounding_side,'multiple'=>$multiple,'summary'=>$summary));
        
        $this->response['prim_cur'] = $prim_cur;
        $this->response['state'] = true;  
    }
}