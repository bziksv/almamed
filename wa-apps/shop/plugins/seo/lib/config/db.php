<?php
return array(
    'shop_seo_category_field' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_seo_category_field_value' => array(
        'group_storefront_id' => array('int', 11, 'null' => 0),
        'category_id' => array('int', 11, 'null' => 0),
        'field_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_storefront_id', 'category_id', 'field_id'),
        ),
    ),
    'shop_seo_category_settings' => array(
        'group_storefront_id' => array('int', 11, 'null' => 0),
        'category_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_storefront_id', 'category_id', 'name'),
        ),
    ),
    'shop_seo_group_category' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'storefront_select_rule_type' => array('enum', "'ANY','INCLUDE','EXCLUDE'", 'null' => 0),
        'category_select_rule_type' => array('enum', "'ANY','INCLUDE','EXCLUDE'", 'null' => 0),
        'sort' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_seo_group_category_category' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'category_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'category_id'),
        ),
    ),
    'shop_seo_group_category_field_value' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'field_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'field_id'),
        ),
    ),
    'shop_seo_group_category_settings' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'name'),
        ),
    ),
    'shop_seo_group_category_storefront' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'storefront' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'storefront'),
        ),
    ),
    'shop_seo_group_storefront' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'storefront_select_rule_type' => array('enum', "'ANY','INCLUDE','EXCLUDE'", 'null' => 0),
        'sort' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_seo_group_storefront_storefront' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'storefront' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'storefront'),
        ),
    ),
    'shop_seo_plugin_settings' => array(
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'name',
        ),
    ),
    'shop_seo_product_field' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_seo_product_field_value' => array(
        'group_storefront_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        'field_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_storefront_id', 'product_id', 'field_id'),
        ),
    ),
    'shop_seo_product_settings' => array(
        'group_storefront_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_storefront_id', 'product_id', 'name'),
        ),
    ),
    'shop_seo_storefront_field' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_seo_storefront_field_value' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'field_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'field_id'),
        ),
    ),
    'shop_seo_storefront_settings' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'name'),
        ),
    ),
);
