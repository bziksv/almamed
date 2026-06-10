<?php

return array(
    'name' => 'Дополнительные поля',
    'description' => 'Выводит дополнительные поля в административной части сайта. ',
    'version' => '1.0',
    'handlers' => array(
        'backend_category_dialog' => 'backendCategoryDialog',
        'category_save' => 'categorySave',
    ),
);
