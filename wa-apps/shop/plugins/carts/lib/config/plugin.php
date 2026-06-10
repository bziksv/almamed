<?php

return array(
    'name' => /*_wp*/('Abandoned carts'),
    'description' => /*_wp*/('Sending notifications about forgotten products'),
    'vendor' => '972539',
    'version' => '4.0.2',
    'shop_settings' => true,
    'img' => 'img/carts.png',
    'frontend' => true,
    'handlers' => array(
        'order_action.create' => 'orderActionCreate',
        'frontend_head' => 'frontendHead',
        'frontend_checkout' => 'frontendCheckout',
        'frontend_cart'   => 'frontendCheckout',
        'frontend_product' => 'frontendProduct',
        'backend_order_edit' => 'backendOrderEdit',
        'backend_reports' => 'backendReports',
        'backend_menu' => 'backendMenu',
        'cart_add' => 'saveStorefront',
        'contacts_delete' => 'contactsDelete',
        'customers_merge' => 'customersMerge',
            'contacts_links' => 'contactsLinks',
            'rights.config' => 'rightsConfig',
    ),
);
