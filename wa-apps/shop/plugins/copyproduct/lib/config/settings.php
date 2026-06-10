<?php

return array(
    'ignore_images' => array(
        'title' => _wd('shop_copyproduct', "Not to copy product images"),
        'description' => _wd('shop_copyproduct', 'If checked, then product images will not be copied'),
        'control_type' => waHtmlControl::CHECKBOX
    ),
    'product_page' => array(
        'title' => _wd('shop_copyproduct', "Button \"Duplicate\" on the product page"),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1
    ),
    'hide_copy' => array(
        'title' => _wd('shop_copyproduct', 'Hide copy'),
        'description' =>  _wd('shop_copyproduct', 'If checked, then duplicates will be hidden'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0
    ),
    'copy_related' => array(
        'title' => _wd('shop_copyproduct', 'Copy related products'),
        'description' =>  _wd('shop_copyproduct', 'If checked, then related products will be linked to the copy'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0
    ),
    'clear_sku' => array(
        'title' => _wd('shop_copyproduct', 'Clear sku'),
        'description' =>  _wd('shop_copyproduct', 'If checked sku will be cleared'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0
    ),
    'clear_sku_name' => array(
        'title' => _wd('shop_copyproduct', 'Clear sku name (if only one sku)'),
        'description' =>  _wd('shop_copyproduct', 'If checked and the product has only one sku, then sku name will be cleared'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0
    ),
);