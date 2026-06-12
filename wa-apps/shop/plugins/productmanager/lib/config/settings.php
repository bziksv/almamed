<?php
return array(
    'mail_to'  => array(
        'title' => "Включить mail to",
        'description'  => "",
        'control_type'=> waHtmlControl::CHECKBOX,
    ),
    'default_email'  => array(
        'title' => "Стандартный майл",
        'description'  => "Заменит email менеджера из его карточки пользователя.",
        'value'        => "",
        'control_type'=> waHtmlControl::INPUT,
    ),
    'manager_group' => array(
        'title' => 'Группа менеджеров',
        'description' => 'Название группы пользователей Webasyst (Контакты → Группы). Можно указать несколько через запятую.',
        'value' => 'Менеджеры по продажам',
        'control_type' => waHtmlControl::INPUT,
    ),
    'manager_pool' => array(
        'title' => 'Выбранные менеджеры',
        'value' => '',
        'control_type' => waHtmlControl::HIDDEN,
    ),
);
