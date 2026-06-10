<?php
return array(
    'shop_slider' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'sort' => array('int', 11),
        'link' => array('varchar', 512, 'null' => 0),
        'img' => array('varchar', 512, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
