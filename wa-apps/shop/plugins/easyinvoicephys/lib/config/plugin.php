<?php

return array(
    'name'        => 'Легкая оплата по счету',
    'description' => 'Оплата безналичным расчетом для юридических лиц (РФ)',
    'icon'        => 'img/icon.png',
    'img'        => 'img/icon.png',
    'version'     => '2.58',
    'vendor' => 972278,
	'author' => 'wa-master',
    'shop_settings' => true,
    'handlers' => array(
		'backend_orders' => 'backendOrders',
		'backend_order' => 'backendOrder',
    ),
);
