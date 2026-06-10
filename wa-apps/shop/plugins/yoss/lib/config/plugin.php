<?php

/*
 * @author Max Severin <makc.severin@gmail.com>
 */
return array(
    'name' => /*_wp*/('Smart search'),
    'description' => /*_wp*/('Product ajax-search with auto-loading'),
    'img' => 'img/yoss.png',
    'vendor' => 1020720,
    'version' => '1.1.2',
    'shop_settings' => true,
    'custom_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'frontend_head' => 'frontendHead',
    ),
);