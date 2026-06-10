<?php

return [
    'debugbar' => [
        'title' => 'Enable debug bar', //Включить панель отладки
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0,
    ],
    'long_events' => [
        'title' => 'Show only long events', //Показывать только долгие события
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],
    'send_stats' => [
        'title' => 'Send anonymous stats',
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],
    /* //Uncomment for VPS
     * 'mysql' => [
        'title' => 'Enable database query logging', //Включить обработку запросов к базе данных
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],
    'long_queries' => [
        'title' => 'Show only long DB queries', //
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],*/
];