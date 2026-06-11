<?php
return array(
    'shop_slider' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'sort' => array('int', 11),
        'link' => array('varchar', 512, 'null' => 0),
        'img' => array('varchar', 512, 'null' => 0),
        'img_tablet' => array('varchar', 512),
        'img_mobile' => array('varchar', 512),
        'alt' => array('varchar', 512),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
