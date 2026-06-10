<?php
class shopCurrencyratesPluginGetrateController extends waJsonController
{ 
    public function execute() {
        $code = waRequest::post('code','',waRequest::TYPE_STRING);
        $rate_model = new shopCurrencyratesModel();
  
        $prim_cur = $rate_model->getPrimaryCurrency();
        $rate = $rate_model->getRate($code);
        $result = $rate_model->query("SELECT dateup, multiple, summary, rounding, rounding_side FROM shop_currencyrates WHERE code = ?",$code)->fetch();
  
        $this->response['rate'] = $rate;
        $this->response['prim_cur'] = $prim_cur;  
        $this->response['date'] = $result['dateup'];
        $this->response['mul'] = $result['multiple'];
        $this->response['sum'] = $result['summary'];
        $this->response['rounding'] = $result['rounding'];
        $this->response['rounding_side'] = $result['rounding_side'];
        $this->response['this_date'] = date("Y-m-d");
    }
}