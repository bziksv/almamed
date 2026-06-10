<?php

class priceparseProductModel extends waModel
{
    protected $table = 'shop_product';

    public function getProductsWithParse()
    {
        $sql = "SELECT 
                s.id,
                s.name,
                p.selector,
                p.link,
                p.price
     FROM $this->table AS s LEFT JOIN priceparse_product AS p ON s.id = p.id_product LIMIT 0, 15";
        return $this->query($sql)->fetchAll();
    }

}
