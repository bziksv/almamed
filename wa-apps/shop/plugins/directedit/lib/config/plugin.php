<?php
/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: http://www.echo-company.ru
 */
return array(
    'name' => _wp("Редактировать в один клик"),
    'description' => _wp("В списках товарах, ссылка/картинка открывает сразу редактирование товара"),
    'vendor' => '962376',
    'version' => '1.2',
    'img'=>'img/directedit16.png',
    'icons'=>array(
        16 => 'img/directedit16.png',
        24 => 'img/directedit24.png',
        48 => 'img/directedit48.png',
    ),
    'handlers' => array(
        'backend_products' => 'on_backend_products',
        'backend_product_edit'=> 'on_backend_product_edit',
    ),
);
