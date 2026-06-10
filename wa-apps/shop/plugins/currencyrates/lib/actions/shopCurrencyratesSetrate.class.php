<?php
class shopCurrencyratesSetrate
{
    public $rates;
    public $date;
 
    public function __construct() { 
        $client = new SoapClient("http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL");
        $this->date = date("Y-m-d");
        $curs = $client->GetCursOnDate(array("On_date" => $this->date));
        $this->rates = new SimpleXMLElement($curs->GetCursOnDateResult->any);
    }
 
    public function getRate($code) {
        $code1 = (int)$code;
        if ($code1 != 0) {
            $result = $this->rates->xpath('ValuteData/ValuteCursOnDate/Vcode[.='.$code.']/parent::*');
        } else {
            $result = $this->rates->xpath('ValuteData/ValuteCursOnDate/VchCode[.="'.$code.'"]/parent::*');
        }
        
        if (!$result) {
            return false; 
        } else {
            $vc = (float)$result[0]->Vcurs;
            $vn = (int)$result[0]->Vnom;
            return ($vc/$vn);
        }
  
    }
}