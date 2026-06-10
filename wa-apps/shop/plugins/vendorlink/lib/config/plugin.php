<?php
return array (
    'name' => _wp('Links to the vendors'),
    'icon' => 'img/vendorlink16.png',
    'img' => 'img/vendorlink16.png',
    'version' => '1.0.3',
    'vendor' => '964801',
    'handlers' =>
        array (
            'backend_product' => 'backendProduct',
            'backend_order' => 'backendOrder',
            'product_delete' => 'productDelete',
            'frontend_product' => 'frontendProduct',
        ),
);
