<?php

return array(
    'name'        => 'Лог пользователей',
    'description' => 'Журнал действий сотрудников с записями блога',
    'img'         => 'img/userlog.png',
    'version'     => '1.0.0',
    'vendor'      => 'almamed',
    'handlers'    => array(
        'post_presave'     => 'postPresave',
        'post_save'        => 'postSave',
        'post_prepublish'  => 'postPrepublish',
        'post_publish'     => 'postPublish',
        'post_predelete'   => 'postPredelete',
    ),
);
