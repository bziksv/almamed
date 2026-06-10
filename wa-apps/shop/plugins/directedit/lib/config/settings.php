<?php
/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: http://www.echo-company.ru
 */
return array(
    'use_product_list'  => array(
        'title'        => _wp('Списки товаров'),
        'description'  => _wp('Задействовать списки товаров'),
        'value'        => '1',
        'control_type'=> waHtmlControl::CHECKBOX,
    ),
    'use_jots'  => array(
        'title'        => _wp('Отзывы'),
        'description'  => _wp('Задействовать раздел Товары->Отзывы'),
        'value'        => '1',
        'control_type'=> waHtmlControl::CHECKBOX,
    ),
    'use_stocks'  => array(
        'title'        => _wp('Склады'),
        'description'  => _wp('Задействовать раздел Товары->Склады'),
        'value'        => '1',
        'control_type'=> waHtmlControl::CHECKBOX,
    ),
    'use_scroll'  => array(
        'title'        => _wp('Пролистывание'),
        'description'  => _wp('Подсветка товара в списках и скрол до товара при нажатие кнопки назад'),
        'value'        => '1',
        'control_type'=> waHtmlControl::CHECKBOX,
    )
);