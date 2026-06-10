<?php
class shopCurrencyratesUpdateCli extends waCliController
{
    //Функция обновления курса
    private function UpdateRates() {
        $cur_model = new shopCurrencyModel();
        $rate_model = new shopCurrencyratesModel();
        $cur_rate = new shopCurrencyratesSetrate();

        $date = date('Y-m-d');
        $query = $cur_model->query("SELECT code FROM shop_currency WHERE code <> 'RUB'")->fetchAll();
        $selected_currency = shopCurrencyratesHelper::getSelectedCurrency();

        foreach ($query as $key =>$val) {
            if (!is_array($selected_currency) || (is_array($selected_currency) && in_array($val['code'], $selected_currency))) {
                $rate = $cur_rate->getRate($val['code']);//Снимаем курс для данной валюты
                if (!empty($rate)) {
                    $rate_model->updateByField('code',$val['code'], array('rate'=>$rate,'dateup'=>$date));
                    $fields = $rate_model->getByField('code',$val['code']);//Берём коэффициенты для данной валюты
                    $rate = $rate*$fields['multiple'];
                    $rate += $fields['summary'];

                    if ($fields['rounding_side'] == 0) {
                        $rate = shopCurrencyratesHelper::RoundingRateFloor($fields['rounding'],$rate);
                    } else if($fields['rounding_side'] == 1) {
                        $rate = shopCurrencyratesHelper::RoundingRateCeil($fields['rounding'],$rate);
                    } else if($fields['rounding_side'] == 2) {
                        $rate = shopCurrencyratesHelper::RoundingRate($fields['rounding'],$rate);
                    }
                    $cur_model->changeRate($val['code'],$rate);
                }
            }
        }
    }

    public function execute() {
        // handle function
        if (extension_loaded('soap')) {
            $cur_model = new shopCurrencyModel();
            $state = shopCurrencyratesHelper::PrimaryCurrencyFlag($cur_model);

            if ($state) {
                $rate_model = new shopCurrencyratesModel();

                //Проверяем есть ли данные в нашей таблице
                $state = $rate_model->query("SELECT code FROM shop_currencyrates")->fetchAll();

                //если данных нет то заносим их из shop_currency
                if (!$state) {
                    $code = Array();
                    $rate = array();
                    $sort = array();
                    $date = null;
                    $query = $cur_model->getAll();

                    foreach ($query as $key=>$val) {
                        $code[] = $val['code'];
                        $rate[] = $val['rate'];
                        $sort[] = $val['sort'];
                    }

                    for ($i = 0; $i < count($code); $i++) {
                        $data = array('code'=>$code[$i],'rate'=>$rate[$i],'sort'=>$sort[$i],'dateup'=>$date);
                        $rate_model->insert($data);
                    }
                } else {//Если данные есть...
                    //Не прроисходило ли добавления валют
                    $data = $cur_model->query('SELECT code FROM shop_currency')->fetchAll();
                    $plugin_data = $rate_model->query('SELECT code FROM shop_currencyrates')->fetchAll();

                    //синхронизация количества валют магазина и плагина
                    shopCurrencyratesHelper::comparingArrays($data, $plugin_data);
                }

                $this->UpdateRates();
            } else {
                return false;
            }
        }
    }
}
