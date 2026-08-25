<?php

return array(
    'log_kp' => array(
        'title'        => 'Писать «Запросить КП»',
        'description'  => 'Сохранять заявки из всплывающей формы на карточке товара',
        'value'        => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'log_zayavka' => array(
        'title'        => 'Писать «Оставить заявку»',
        'description'  => 'Страница /ostavit-zayavku/',
        'value'        => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'log_404' => array(
        'title'        => 'Писать форму с 404',
        'value'        => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'log_wait' => array(
        'title'        => 'Писать окно при уходе',
        'description'  => 'Плагин wait, если форма собирает контакты',
        'value'        => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'store_payload' => array(
        'title'        => 'Хранить сырой JSON (payload)',
        'description'  => 'Полный POST в карточке заявки. Можно выключить для экономии места / ПДн',
        'value'        => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'show_badge' => array(
        'title'        => 'Badge «новых» в меню',
        'description'  => 'Показывать (N) рядом с пунктом «Заявки»',
        'value'        => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'duplicate_minutes' => array(
        'title'        => 'Окно антидублей (минуты)',
        'description'  => 'Повтор с тем же телефоном за N минут помечается как дубль. 0 = выкл.',
        'value'        => 10,
        'control_type' => waHtmlControl::INPUT,
    ),
    'retention_months' => array(
        'title'        => 'Хранить заявки (месяцев)',
        'description'  => 'Старше этого срока можно удалить кнопкой «Очистить старые» в списке. 0 = не удалять',
        'value'        => 24,
        'control_type' => waHtmlControl::INPUT,
    ),
);
