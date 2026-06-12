<?php

return array(
    'name' => 'Менеджер продукта',
    'description' => 'Назначение менеджеров на товары, статистика по категориям',
    'img'  => 'img/brands.png',
    'version' => '2.2.3',
    'custom_settings' => true,
    'handlers' => array(
        'backend_menu' => 'backendMenu',
        'product_presave' => 'productPresave',
        'backend_product' => 'product_edit',
        'backend_products' => 'backend_products_all',
        'frontend_product' => 'front_product',
    )
);