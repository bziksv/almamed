<?php
return array (
  'name' => /*_wp*/'PlugMein',
  'icon' => 'img/plugmein.png',
  'version' => '3.0',
  'vendor' => '991739',
  'description' => 'Plugin Manager',
  'shop_settings' => true,
  'custom_settings' => true,
    'handlers' => array(
        'backend_settings' => 'sendStat',
        'routing' => 'routingHook',
        '*' => array(
            array(
                'event' => '/.*/',
                'class' => 'shopPlugmeinPlugin',
                'method' => 'allHook'
            )
        ),
    )
);
