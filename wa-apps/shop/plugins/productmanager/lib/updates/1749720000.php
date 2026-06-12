<?php

$model = new waModel();

try {
    $model->query('SELECT category_id FROM shop_productmanager_category WHERE 0');
} catch (waDbException $e) {
    $model->exec(
        'CREATE TABLE IF NOT EXISTS shop_productmanager_category (
            category_id INT(11) NOT NULL,
            manager_id INT(11) NOT NULL,
            updated DATETIME DEFAULT NULL,
            PRIMARY KEY (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
    );
}
