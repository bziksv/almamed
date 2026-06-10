<?php

$model = new waModel();

// delete rows with nonexistent stocks
$sql = "DELETE ps FROM shop_product_stocks ps
        LEFT JOIN shop_stock s ON ps.stock_id = s.id
        WHERE s.id IS NULL";
$model->exec($sql);