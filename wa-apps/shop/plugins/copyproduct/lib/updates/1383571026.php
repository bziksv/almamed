<?php

$model = new waModel();

// delete incorrect rows
$sql = "DELETE FROM shop_product_stocks WHERE stock_id = 0";
$model->exec($sql);
