<?php
class shopCurrencyratesHelper
{    
    /**
     * Проверка соответствия основной валюты магазина
     * Российскому рублю
     *
     * @param shopCurrencyModel $cur_model
     * @return bool
    */
    public static function PrimaryCurrencyFlag(shopCurrencyModel $cur_model) {
        $prim_cur = $cur_model->getPrimaryCurrency();
        $result = false;
        
        if ($prim_cur == 'RUB') {
            $result = true;
        }
  
        return $result;
    }
    
    /**
     * Сравнение данных о валютах магазина и плагина
     * 
     * @param array $shop_data
     * @param array $plugin_data
     *
     * @return bool
    */
    public static function comparingArrays($shop_data, $plugin_data) {
        $mas_one = array();
        $mas_two = array();
        
        foreach ($shop_data as $key => $val) {
            $mas_one[] = $val['code'];
        }
      
        foreach ($plugin_data as $key => $val) {
            $mas_two[] = $val['code'];
        }  
      
        $mas = array_diff($mas_one, $mas_two);
        
        if (count($mas) != 0) {
            self::updatePluginData($mas,1);
        } else {
            $mas = array_diff($mas_two, $mas_one);
            if (count($mas) != 0) {
                self::updatePluginData($mas,2);
            }
        } 
        return true;       
    }
    
    /**
     * Обновление данных о валютах в таблицах плагина
     *
     * @param array $mas
     * @param int $flag - допустимые значения (1 | 2)
    */
    public static function updatePluginData($mas, $flag = 1) {
        $rate_model = new shopCurrencyratesModel();
        $cur_model = new shopCurrencyModel();
        
        //Добаление недостающих валют 
        if ($flag == 1) {
            $date = null;
            foreach ($mas as $key=>$val) {
                $data = $cur_model->getByField('code',$val);
                $data['date'] = $date;
                $rate_model->addCurrency($data);
            }
        //Удаление лишних валют
        } else if($flag == 2) {
            foreach ($mas as $key => $val) {
                $rate_model->deleteCurrency($val);
            }
        }
    }
    
    /**
     * Получение списка выбранных валют для 
     * обновления через cron
     *
     * @return array | null
    */
    public static function getSelectedCurrency() {
        $app_settings_model = new waAppSettingsModel();
        $result = json_decode($app_settings_model->get('shop.currencyrates', 'selected_currency_cron'));
        
        if (!is_array($result)) {
            $result = null;
        }
        
        return $result;
    }
    
    //Функция округления до ближайшего числа
    public static function RoundingRate($state, $rate) {
        $result = $rate;
        
        if ($state == 1) {
            $result = round($result);
        } else if($state==2) {
            $result = round($result,1);
        } else if ($state==3) {
            $result = round($result,2);
        }
  
        return $result;
    }
 
    //Функция округления до меньшего числа
    public static function RoundingRateFloor($state, $rate) {
        $result = $rate;
        if ($state == 1) {
            $result = floor($result);
        } else if($state==2) {
            $result = floor($result*10)/10;
        } else if ($state==3) {
            $result = floor($result*100)/100;
        }
  
        return $result;
    }
 
    //Функция округления до большего числа
    public static function RoundingRateCeil($state, $rate) {
        $result = $rate;
        if ($state == 1) {
            $result = ceil($result);
        } else if($state==2) {
            $result = ceil($result*10)/10;
        } else if ($state==3) {
            $result = ceil($result*100)/100;
        }
        
        return $result;
    }
}