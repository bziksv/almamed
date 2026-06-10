<?php
class shopCurrencyratesPluginUpdateController extends waJsonController
{
    public function execute() {
        $code = waRequest::post('code','',waRequest::TYPE_ARRAY);
      
        $cur_rate = new shopCurrencyratesSetrate();
        $rate = Array();//Для курса по ЦБ
        $tmp_rate = Array();//Для умноженного курса
        $state = Array();//Есть или нет курс для данной валюты в ЦБ
        $error = Array();
        $rate_model = new shopCurrencyratesModel();
        $cur_model = new shopCurrencyModel();
        
        for ($i=0; $i<count($code); $i++) {
            $rate[$code[$i]] = $cur_rate->getRate($code[$i]);
            if (!empty($rate[$code[$i]])) {
                $rate_model->updateByField('code',$code[$i],array('rate'=>$rate[$code[$i]],'dateup'=>date('Y-m-d')));
                $fields = $rate_model->getByField('code',$code[$i]);
                $tmp_rate[$code[$i]] = $rate[$code[$i]] * (float)$fields['multiple'];
                $tmp_rate[$code[$i]] += (float)$fields['summary'];
         
                if ($fields['rounding_side'] == 0) {
                    $tmp_rate[$code[$i]] = shopCurrencyratesHelper::RoundingRateFloor($fields['rounding'],$tmp_rate[$code[$i]]);
                } else if($fields['rounding_side'] == 1) {
                    $tmp_rate[$code[$i]] = shopCurrencyratesHelper::RoundingRateCeil($fields['rounding'],$tmp_rate[$code[$i]]);
                } else if($fields['rounding_side'] == 2) {
                    $tmp_rate[$code[$i]] = shopCurrencyratesHelper::RoundingRate($fields['rounding'],$tmp_rate[$code[$i]]);
                }
                
                $cur_model->changeRate($code[$i],$tmp_rate[$code[$i]]);
                $state[$code[$i]] = true;
            } else {
                $state[$code[$i]] = false;
                $error[$code[$i]] = "Центробанк не имеет курса данной валюты к рублю";
            }   
        }  
      
        $this->response['rate'] = $rate;
        $this->response['tmp_rate'] = $tmp_rate;
        $this->response['state'] = $state;
        $this->response['error'] = $error;
        $this->response['date'] = date('Y-m-d');
    } 
}