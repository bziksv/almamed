<?php

return array(
    'active'  => array(
        'title'        => 'Активность:',
        'description'  => array(
            ''
        ),
        'value'        => 1, // значение по умолчанию
        'control_type'=> waHtmlControl::CHECKBOX,
    ),
    'category_count'  => array(
        'title'        => 'Количество товаров в категории:',
        'description'  => array(
            'Количество товаров в категории при котором нужно выводить дополнительные товары'
        ),
        'value'        => 3, // значение по умолчанию
        'control_type'=> waHtmlControl::INPUT,
    ),
    'count'  => array(
        'title'        => 'Количество товаров:',
        'description'  => array(
            'Количество товаров на странице'
        ),
        'value'        => 30, // значение по умолчанию
        'control_type'=> waHtmlControl::INPUT,
    ),
    'categories' => array(
        'title' => 'Категории',
        'description' => 'Категории с параметром product_additional=hide [ Скрыть вывод плагина ]',
        'control_type' => waHtmlControl::CUSTOM.' '.'shopProductadditionalPlugin::settingCustomControl',
    ),
);
