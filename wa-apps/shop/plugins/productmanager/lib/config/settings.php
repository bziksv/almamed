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
        'value'        => "", // значение по умолчанию
        'control_type'=> waHtmlControl::INPUT,
    ),
);
