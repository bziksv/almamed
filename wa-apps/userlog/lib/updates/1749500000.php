<?php

$schema = include wa()->getAppPath('lib/config/db.php', 'userlog');
$model = new waModel();
foreach ($schema as $table => $fields) {
    try {
        $model->query('SELECT 1 FROM '.$table.' LIMIT 1');
    } catch (Exception $e) {
        $model->createSchema($table, $fields);
    }
}
