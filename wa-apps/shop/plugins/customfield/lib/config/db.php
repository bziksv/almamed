<?php
return array(
    'shop_customfield_menu' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'category_id' => array('int', 11),
        'name' => array('varchar', 255),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
