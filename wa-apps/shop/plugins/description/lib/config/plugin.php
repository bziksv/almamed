<?php

return array(
    'name' => 'Товары без описания', // название плагина
    'img'  => 'img/brands.png', // относительный путь к файлу иконки плагина (16*16px), обычно в поддиректории img/
    'custom_settings' => true,
    'handlers' => array(
        'backend_product' => 'product_edit',
    ),
);