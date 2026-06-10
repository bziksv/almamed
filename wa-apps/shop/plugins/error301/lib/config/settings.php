<?php 
return array(	
	'debug' => array(
        'title' => 'Режим отладки',
        'control_type' => waHtmlControl::SELECT,
		'description' => '<p style="color:red;font-weight:bold;">Включать только для отладки. В режиме "Отладки" переадресация отключена.</p>',
        'value' => 0,
		'options' => array(
            array(
                'value' => 0,
                'title' => 'нет',
            ),
            array(
                'value' => 1,
                'title' => 'да',
            ),
        ),
    ),
    'root' => array(
        'title' => 'Переадресация на главную страницу',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'Переадресовывать посетителя на главную страницу сайта, если не найдена информация для переадресации',
        'value' => 0,
    ), 
	'check' => array(
        'title' => 'Проверка адреса перед переадресацией',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'Проверять адрес на ошибку 404 перед переадресацией. При отключенной проверке не осуществляется поддержка переадресация на адреса плагина "SEO-фильтр"',
        'value' => 1,
    )
);