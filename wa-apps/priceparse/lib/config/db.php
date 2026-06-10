<?php

return array(
    'priceparse_product' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'id_product' => array('int', 11, 'null' => 0),
        'selector' => array('varchar',255, 'null' => 0),
        'link' => array('varchar',255, 'null' => 0),
        'price' => array('varchar',255, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'datetime' => 'datetime',
        ),
    ),
);
