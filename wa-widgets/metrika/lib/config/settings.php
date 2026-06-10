<?php
return array(
    'color' => array(
        'title' => /*_w*/('Фон виджета'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value'       => '#ffffff',
                'title'       => /*_w*/('Белый'),
            ),
            array(
                'value'       => '#ffebeb',
                'title'       => /*_w*/('Красный'),
            ),
            array(
                'value'       => '#e4ffdc',
                'title'       => /*_w*/('Зеленый'),
            ),
            array(
                'value'       => '#fffada',
                'title'       => /*_w*/('Желтый'),
            ),
            array(
                'value'       => '#edfbff',
                'title'       => /*_w*/('Голубой'),
            ),
        )
    ),
    'period' => array(
        'title' => /*_w*/('Период'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value'       => '1',
                'title'       => /*_w*/('1 день'),
            ),
            array(
                'value'       => '7',
                'title'       => /*_w*/('7 дней'),
            ),
            array(
                'value'       => '30',
                'title'       => /*_w*/('30 дней'),
            ),
        )
    ),
    'number' => array(
        'title' => /*_wp*/('Номер счетчика'),
        'control_type' => waHtmlControl::INPUT,
        'description' => 'Узнать код счетчика можно по адресу: <a href="http://metrika.yandex.ru" target="_blank">http://metrika.yandex.ru</a>',
    ),
    'login' => array(
        'title' => /*_wp*/('Имя пользователя'),
        'control_type' => waHtmlControl::INPUT,
        'description' => 'Имя пользователя на Яндекс (без @yandex). Например: petya2015'
    ),
    'password' => array(
        'title' => /*_wp*/('Пароль на Яндекс'),
        'control_type' => waHtmlControl::PASSWORD,
        'title'       => 'Пароль на Яндекс'
    ),
    'client_id' => array(
        'title' => /*_wp*/('OAuth ID'),
        'control_type' => waHtmlControl::PASSWORD,
        'description' => 'Получить <b><i>OAuth ID</i></b> можно по <a href="https://oauth.yandex.ru/client/new" target="_blank">ссылке</a>'
    ),
    'client_secret' => array(
        'title' => /*_wp*/('OAuth Пароль'),
        'control_type' => waHtmlControl::PASSWORD,
        'description' => 'Получить <b><i>OAuth Пароль</i></b> можно по <a href="https://oauth.yandex.ru/client/new" target="_blank">ссылке</a>'
    ),
);