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
        'enabled' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'date_from' => array('date'),
        'date_to' => array('date'),
        'sales_manager' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'sales_manager_id' => array('int', 11),
        'content_manager' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'content_manager_id' => array('int', 11),
        'views_count' => array('int', 11, 'null' => 0, 'default' => '0'),
        'clicks_count' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
