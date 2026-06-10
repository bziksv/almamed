<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
return array(
    'name' => /*_wp*/('Brands'),
    'description' => /*_wp*/('Storefront’s product filtering by brand (manufacturer). You can upload image and add description for the brand.'),
    'version' => '3.0.0',
    'vendor' => 809114,
    'img'=>'img/brands.png',
    'shop_settings' => true,
    'frontend'    => true,
    'icons'=>array(
        16 => 'img/brands.png',
    ),
    'handlers' => array(
        'frontend_nav' => 'frontendNav',
        'frontend_nav_aux' => 'frontendNavAux',
        'backend_products' => 'backendProducts',
        'backend_extended_menu' => 'backendExtendedMenu',
        'products_collection' => 'productsCollection',
        'sitemap' => 'sitemap'
    ),
);

