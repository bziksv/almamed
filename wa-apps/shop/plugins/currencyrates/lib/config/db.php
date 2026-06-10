<?php
return array(
 'shop_currencyrates' => array(
  'code' => array('char', 3, 'null' => 0),
  'rate' => array('decimal', "18,10", 'null' => 0, 'default' => '1.0000000000'),
  'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
  'multiple' => array('decimal',"8,3", 'unsigned', 'null' => 0, 'default' => '1.000'),
  'summary' => array('decimal',"8,3",'null' => 0, 'default' => '0.000'),
  'rounding' => array('int', 1, 'unsigned', 'null'=>0, 'default'=>'2'),
  'rounding_side' => array('int', 1, 'unsigned', 'null'=>0, 'default'=>'2'),
  'dateup'=>array('date'),  
        ':keys' => array(
            'PRIMARY' => 'code',
        ),
 ),
);