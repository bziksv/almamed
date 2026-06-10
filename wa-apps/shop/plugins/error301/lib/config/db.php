<?php
return array(
    'shop_error301' => array(
        'id' => array('int', 11),
        'type' => array('varchar', 1),
        'url' => array('varchar', 255),
        'parent' => array('int', 11),
        ':keys' => array(
            'unqkey' => array('type', 'url', 'parent', 'unique' => 1),
        ),
    ),
);
