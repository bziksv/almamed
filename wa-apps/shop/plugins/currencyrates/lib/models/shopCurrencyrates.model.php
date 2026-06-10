<?php
class shopCurrencyratesModel extends waModel
{
     protected $table = 'shop_currencyrates';
     protected $id = 'code';
     
    public function getPrimaryCurrency() {
       $result = $this->query("SELECT code FROM `{$this->table}` WHERE sort = 0")->fetch();
       return $result['code'];     
    }
      
    public function getRate($code) {
       $data['code'] = $code;
       $result = $this->query("SELECT rate FROM `{$this->table}` WHERE code = s:code", $data)->fetch();
       return (float)$result['rate'];
    }
     
      //Выводим всю таблицу shop_currency_rates
    public function getAll($key = null, $normalize = false) {
       $result = $this->query("SELECT * FROM `{$this->table}`")->fetchAll($key, $normalize);
       
        foreach ($result as & $item) {
            $item['rate'] = (double) $item['rate'];
        }
        
       return $result;
    }
    
    //Добавляем запись в таблицу
    public function addCurrency($cur_mas) {
       $this->insert($cur_mas);
       return true;
    }
      
    //Удаляем запись о валюте из таблицы
    public function deleteCurrency($code) {
       $data['code'] = $code;
       $this->query("DELETE FROM `{$this->table}` WHERE code= s:code",$data);
       return true;
    }
}