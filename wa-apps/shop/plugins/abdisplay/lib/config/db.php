<?php

return array(
    'shop_abdisplay_event' => array(
        'id'              => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'experiment_key'  => array('varchar', 64, 'null' => 0, 'default' => ''),
        'abtest_id'       => array('int', 11, 'null' => 0, 'default' => 0),
        'variant_id'      => array('int', 11, 'null' => 0, 'default' => 0),
        'variant_code'    => array('varchar', 16, 'null' => 0, 'default' => ''),
        'event'           => array('varchar', 32, 'null' => 0, 'default' => ''),
        'url'             => array('varchar', 512, 'null' => 0, 'default' => ''),
        'contact_id'      => array('int', 11, 'null' => 0, 'default' => 0),
        'session_id'      => array('varchar', 64, 'null' => 0, 'default' => ''),
        'create_datetime' => array('datetime', 'null' => 0),
        'meta'            => array('text'),
        ':keys'           => array(
            'PRIMARY'            => 'id',
            'experiment_event'   => array('experiment_key', 'event', 'create_datetime'),
            'abtest_variant'     => array('abtest_id', 'variant_id'),
            'create_datetime'    => 'create_datetime',
        ),
    ),
);
