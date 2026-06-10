<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 15/8/15
 * Time: 11:42 AM
  */

class shopVendorlinkLinksModel extends waModel {
    protected $table = 'shop_vendorlink_links';

    public function getLinksByProductId($product_id) {
        $links = $this->getByField('product_id', $product_id, true);
        return $links;
    }
}