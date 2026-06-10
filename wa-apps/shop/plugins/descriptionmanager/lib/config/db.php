<?php
return array(
    'shop_descriptionmanager' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'product_id' => array('int', 11, 'null' => 0),
        'description' => array('varchar', 512, 'null' => 0),
        'price' => array('varchar', 512, 'null' => 0),
        'delivery' => array('varchar', 512, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
