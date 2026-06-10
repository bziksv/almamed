<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
return array(
    'name' => /*_wp*/('Product duplication'),
    'description' => /*_wp*/('Allows to create product duplicates'),
    'img' => 'img/logo.png',
    'vendor' => 809114,
    'version' => '2.5',
    'handlers' => array(
        'backend_product' => 'backendProduct',
        'backend_products' => 'backendProducts',
        'backend_product_edit' => 'backendProductEdit',
    )
);
