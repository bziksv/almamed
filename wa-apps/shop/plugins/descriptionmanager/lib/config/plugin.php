<?php

return [
    'name' => 'Описание для менеджеров',
    'description' => 'Описание к товарам, для менеджеров. Цены и доставка.',
    'img' => 'img/brands.png',
    'version' => '1.0.0',
    'frontend' => true,
    'handlers' =>
        array (
            'backend_product' => 'backendProductDescription',
            'frontend_product' => 'frontendProduct'
        ),
];
