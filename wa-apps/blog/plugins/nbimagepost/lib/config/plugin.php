<?php

return array(
    'name' => _wp('nb imagePost'),
    'description' => _wp('nb description'),
    'img'  => 'image/icon.png',
    'version' => '1.0.0',
    'vendor' => 1052580,
    'handlers' => array(
        "backend_post_edit" => "backendPostEdit",
        //"backend_stream" => "backendStream",
        "backend_assets" => "blogHeader",
    )
);