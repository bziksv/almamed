<?php

/**
 * @author wa-apps <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
return array(
    'name' => /*_wp*/('Product features'),
    'description' => /*_wp*/('This plugin allow you edit features from product list page'),
    'img' => 'img/logo.png',
    'vendor' => 809114,
    'version' => '1.6',
    'shop_settings' => true,
    'handlers' => array(
        'backend_products' => 'backendProducts',
    )
);