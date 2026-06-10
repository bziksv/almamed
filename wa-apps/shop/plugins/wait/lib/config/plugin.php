<?php

return array(
    'name'              => 'Всплывающее окно при уходе с сайта',
    'img'               => 'img/wait.png',
    'description'       => 'Предложите скидку или попросите оставить телефон/e-mail',
    'version'           => '2.04',
    'vendor'            => 973724,
    'custom_settings'   => true,
    'frontend'          => true,
    'handlers' => array(
        'frontend_footer' => 'frontendFooter',
        'frontend_checkout' => 'frontendCheckout',
        'order_action.create' => 'orderActionCreate',
    ),
);