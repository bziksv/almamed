<?php
return array(
 'shop_wmimageincat_images' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'category_id' => array('int', 11, 'null' => 0),
        'type_image' => array('varchar', 10, 'null' => 0),
        'upload_datetime' => array('datetime', 'null' => 0),
        'file_name' => array('varchar', 255, 'null' => 0),
        'ext' => array('varchar', 10, 'null' => 0),
        'width' => array('int', 5, 'null' => 0, 'default' => '0'),
        'height' => array('int', 5, 'null' => 0, 'default' => '0'),
        'size' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    )
);