<?php
return array(
    'shop_breadcrumbs_blog_settings' => array(
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'name',
        ),
    ),
    'shop_breadcrumbs_seofilter_feature' => array(
        'storefront' => array('varchar', 255, 'null' => 0),
        'feature_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'sort' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('storefront', 'feature_id'),
            'storefront' => 'storefront',
        ),
    ),
    'shop_breadcrumbs_settings' => array(
        'storefront' => array('varchar', 255, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('storefront', 'name'),
            'storefront' => 'storefront',
        ),
    ),
    'shop_breadcrumbs_theme_settings' => array(
        'app' => array('varchar', 64, 'null' => 0, 'default' => 'shop'),
        'theme_id' => array('varchar', 50, 'null' => 0),
        'name' => array('varchar', 50, 'null' => 0),
        'value' => array('longtext'),
        ':keys' => array(
            'PRIMARY' => array('app', 'theme_id', 'name'),
        ),
    ),
);
