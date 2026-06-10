<?php
class shopCurrencyratesPluginSettingsAction extends waViewAction
{
    /**
     * Получение требуемого списка валют магазина, исключая основную валюту
    */
    private function getCurrenciesList(shopCurrencyModel $cur_model) {
        $cur_list = $cur_model->query("SELECT code, rate FROM shop_currency WHERE code <>'RUB'")->fetchAll('code');
        
        if ($cur_list) {
            foreach ($cur_list as $key => $value) {
                $result[$key] = waCurrency::getInfo($key);
                $cur_list[$key]['title'] = $result[$key]['title'];
                $cur_list[$key]['sign'] = $result[$key]['sign'];
            }
            
            return $cur_list;
        } else {
            return array();
        }
    }
 
    public function execute() {
        //Если расширение SOAP подключено
        if (extension_loaded('soap')) {
            $cur_model = new shopCurrencyModel();
            $state = shopCurrencyratesHelper::primaryCurrencyFlag($cur_model);
            
            //Если основной валютой является Российский рубль
            if ($state) {
            ///////Передаём во view данные об основной валюте////////////////
                $rate_model = new shopCurrencyratesModel();
                $prim_cur = $cur_model->getCurrencies('RUB');
                
                $this->view->assign('pc_code', $prim_cur['RUB']['code']);
                $this->view->assign('pc_title', $prim_cur['RUB']['title']);
            /////////////////////////////////////////////////////////////////

            /////Передаём во view данные о других валютах//////////////////////////
                $cur_list = $this->getCurrenciesList($cur_model);
                $this->view->assign('cur_list', $cur_list);
            //////////////////////////////////////////////////////////////////////
            
                //Кнопка обновления курсов всех валют
                $update_all_rates =  '';
                
                //Проверяем наличие данных в таблице плагина
                $plugin_data = $rate_model->query("SELECT code FROM shop_currencyrates")->fetchAll();
                
                //Если данных в таблице плагина нет
                if (empty($plugin_data)) {
                    $code = Array();
                    $rate = array();
                    $sort = array();
                    $date = null;
                    $states_strings = Array();
                    $query = $cur_model->getAll();
                    
                    foreach ($query as $key => $val) {
                        $code[] = $val['code'];
                        $rate[]=$val['rate'];
                        $sort[] = $val['sort'];
                    }
                    
                    for ($i = 0; $i < count($code); $i++) {
                        $states_strings[$code[$i]] = "<h5 style='color: red;' class='not_update_rate'>Курс не обновлён по ЦБ</h5>";
                        $data = array('code'=>$code[$i],'rate'=>$rate[$i],'sort'=>$sort[$i],'dateup'=>$date);
                        $rate_model->insert($data);
                    }
	  
                    $this->view->assign('state_string', $states_strings);
                    $update_all_rates =  '<div id="update_all_rate"><a href="javascript:void(0);" class="button yellow">Обновить курс всех валют</a></div>';
                } else { //Если данные в таблице плагина есть
                    $states_strings = Array();
                    $date = date('Y-m-d');
                    
                    //Не прроисходило ли добавления валют
                    $data = $cur_model->query('SELECT code FROM shop_currency')->fetchAll();
                    $plugin_data = $rate_model->query('SELECT code FROM shop_currencyrates')->fetchAll();
                    
                    //синхронизация количества валют магазина и плагина
                    shopCurrencyratesHelper::comparingArrays($data, $plugin_data);
                    
                    //запись для каждой валюты
                    $plugin_data = $rate_model->getAll();
                    foreach ($plugin_data as $key => $val) {
                        if ($val['code'] != 'RUB') {
                            if ($val['dateup'] == $date) {
                                $states_strings[$val['code']] = "<h5 style='color: green;'>Курс на ".$date."</h5>";
                            } else if(($val['dateup'] != $date) && ($val['dateup'] != null)) {
                                $states_strings[$val['code']] = "<h5 style='color: red;' class='not_update_rate'>Курс на ".$val['dateup']."</h5>";
                                $update_all_rates =  '<div id="update_all_rate"><a href="javascript:void(0);" class="button yellow">Обновить курс всех валют</a></div>';
                            }   else if($val['dateup'] == null) {
                                $states_strings[$val['code']] = "<h5 style='color: red;' class='not_update_rate'>Курс не по ЦБ ".$val['dateup']."</h5>";
                                $update_all_rates =  '<div id="update_all_rate"><a href="javascript:void(0);" class="button yellow">Обновить курс всех валют</a></div>';
                            }
                        }
                    }
                    $this->view->assign('state_string',$states_strings);
                }
            
                $this->view->assign('update_all_rates', $update_all_rates);
                
                $selected_currency = shopCurrencyratesHelper::getSelectedCurrency();
                
                $this->view->assign('selected_currency', $selected_currency);
                
                //Закидываем путь скрипта для консоли
                $cmd_path = wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR ."cli.php shop CurrencyratesUpdate";
                $this->view->assign('cmd_path',$cmd_path);
            } else {
                $this->view->assign('menu','<h2>Ошибка: "Российский рубль" не является основной валютой!</h2>');
            }
        } else {
            $this->view->assign('menu','<h2>Не подключено расширение SOAP, необходимое для работы плагина!</h2>');
        }
    }
}