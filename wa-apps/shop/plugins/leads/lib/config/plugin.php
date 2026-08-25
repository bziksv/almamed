<?php

return array(
    'name'        => 'Заявки',
    'description' => 'Журнал заявок с форм сайта (КП, «Оставить заявку»)',
    'img'         => 'img/leads.png',
    'version'     => '1.2.2',
    'vendor'      => 'almamed',
    'frontend'    => false,
    'shop_settings' => true,
    'custom_settings' => true,
    'handlers'    => array(
        'backend_menu' => 'backendMenu',
    ),
);
