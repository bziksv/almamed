<?php

return array(
    'name' => 'Поиск',
    'description' => 'Поиск по текстовым страницам сайта',
    'vendor' => '972539',
    'version' => '0.0.3',
    'img' => 'img/search.png',
    'frontend' => true,
    'custom_settings' => true,
    'handlers' => array(
        'backend_sidebar' => 'backendSidebar',
    ),
);