<?php

class priceparseParser
{
    public $html;

    function __construct($url = null) {
        if($url)
            $this->html = file_get_contents($url,true);
        else
            return false;
    }

    public function find($selector){
        preg_match('#'.$selector.'">(.+?)<#is', $this->html, $arr);
        if(isset($arr[1]) && strlen($arr[1]) > 0)
            return $arr[1];
        else
            return false;
    }

}