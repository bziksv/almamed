<?php

return array(
    'se' => array(
        'title' => 'Поисковая система',
        'control_type' => waHtmlControl::SELECT,
        'value' => '0',
        'options' => array(
            array(
                'value'       => '0',
                'title'       => 'Яндекс',
            ),
            array(
                'value'       => '1',
                'title'       => 'Google',
            ),
            array(
                'value'       => '3',
                'title'       => 'Спутник',
            ),
        )
    ),

    'city' => array(
        'title' => 'Город',
        'control_type' => waHtmlControl::SELECT,
        'value' => '213',
        'options' => array(
            array(
                'value'       => '213',
                'title'       => 'Москва',
            ),
            array(
                'value'       => '2',
                'title'       => 'Санкт-Петербург',
            ),
            array(
                'value'       => '143',
                'title'       => 'Киев',
            ),
            array(
                'value'       => '157',
                'title'       => 'Минск',
            ),
        )
    ),

);