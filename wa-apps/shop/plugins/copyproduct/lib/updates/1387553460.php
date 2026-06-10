<?php

$model = new waModel();
$sql = "UPDATE shop_product p
        LEFT JOIN shop_product_reviews r ON p.id = r.product_id
        SET p.rating = 0, p.rating_count = 0
        WHERE p.rating_count > 0 AND r.id IS NULL";