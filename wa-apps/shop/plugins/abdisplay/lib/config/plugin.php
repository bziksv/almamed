<?php

return array(
    'name'            => 'A/B отображение',
    'description'     => 'Каркас A/B для витрины: варианты по cookie, лог view/cart/lead/order',
    'img'             => 'img/abdisplay.png',
    'version'         => '0.1.0',
    'vendor'          => 'almamed',
    'shop_settings'   => true,
    'custom_settings' => true,
    'handlers'        => array(
        'frontend_head'       => 'frontendHead',
        'frontend_product'    => 'frontendProduct',
        'cart_add'            => 'cartAdd',
        'order_action.create' => 'orderActionCreate',
        'backend_menu'        => 'backendMenu',
    ),
);
