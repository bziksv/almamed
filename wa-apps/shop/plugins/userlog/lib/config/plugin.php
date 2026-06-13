<?php

return array(
    'name'        => 'Лог пользователей',
    'description' => 'Журнал действий сотрудников, корзина и откат изменений',
    'img'         => 'img/userlog.png',
    'version'     => '1.0.0',
    'vendor'      => 'almamed',
    'handlers'    => array(
        'product_presave'  => 'productPresave',
        'product_save'     => 'productSave',
        'product_delete'   => 'productDelete',
        'category_save'    => 'categorySave',
        'category_delete'  => 'categoryDelete',
    ),
);
