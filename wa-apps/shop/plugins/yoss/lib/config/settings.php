<?php

/*
 * @author Max Severin <makc.severin@gmail.com>
 */
return array(
    'status' => array(
        'title'        => _wp('Status'),
        'value'        => 'off',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'off' => _wp('Off'),
            'on'  => _wp('On'),
        ),
    ),
    'frontend_head_status' => array(
        'title'        => _wp('Status of frontend_head hook'),
        'description'  => _wp('Turn on to automatically output plugin via frontend_head hook.'),
        'value'        => 'on',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'off' => _wp('Off'),
            'on'  => _wp('On'),
        ),
    ),
    'id_in_html' => array(
        'title'        => _wp('Search input selector'),
        'description'  => _wp('Specify the ID or class of the html element,<br />when filling which will open a search results.<br />Must be like «<b>#search</b>» or «<b>.search</b>».'),
        'placeholder'  => '#search',
        'value'        => '#search',
        'control_type' => waHtmlControl::INPUT,
    ),
    'product_limit' => array(
        'title'        => _wp('Products limit'),
        'description'  => _wp('Number of items in the search results.'),
        'value'        => '10',
        'placeholder'  => '10',
        'control_type' => waHtmlControl::CUSTOM.' '.'shopYossPlugin::settingNumberControl',
    ),
    'min_char_count' => array(
        'title'        => _wp('Characters limit'),
        'description'  => _wp('Number of characters in the search query, after filling that triggered search.'),
        'value'        => '2',
        'placeholder'  => '2',
        'control_type' => waHtmlControl::CUSTOM.' '.'shopYossPlugin::settingNumberControl',
    ),
    'lazy_loading' => array(
        'title'        => _wp('Lazy loading'),
        'description'  => _wp('To enable lazy loading of items in search results.'),
        'value'        => 'off',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'off' => _wp('Off'),
            'on'  => _wp('On'),
        ),
    ),
    'result_max_height' => array(
        'title'        => _wp('Max height (px)'),
        'description'  => _wp('Max height of search result block.'),
        'value'        => '400',
        'placeholder'  => '400',
        'control_type' => waHtmlControl::CUSTOM.' '.'shopYossPlugin::settingNumberControl',
    ),
    'result_width' => array(
        'title'        => _wp('Width (px)'),
        'description'  => _wp('Width of search result block.'),
        'value'        => 'auto',
        'placeholder'  => 'auto',
        'control_type' => waHtmlControl::INPUT,
    ),
    'result_css' => array(
        'title'        => _wp('CSS styles'),
        'description'  => _wp('CSS styles of result block.'),
        'value'        => '.yoss-result { background-color: #fff; border: 1px solid rgba(155, 155, 155, 0.5); border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; left: 0; margin: 0; max-height: 400px; min-height: 40px; overflow-x: hidden; overflow-y: auto; position: absolute; top: 36px; width: auto; z-index: 9999; }
.yoss-result.no-products { color: #444; line-height: 40px; text-align: center; width: inherit; }
.yoss-result.yoss-error { color: #de4d2c; line-height: 40px; text-align: center; width: inherit; }
.yoss-result .yoss-result-wrapper { border-bottom: 1px dotted #dbdbdb; float: left; width: 100%; }
.yoss-result .yoss-result-wrapper:last-child { border-bottom: 0 none; }
.yoss-result .yoss-result-product-count { display: block; float: left; font-size: 16px; font-weight: bold; margin: 29px 0; position: relative; text-align: center; width: 40%; }
.yoss-result .yoss-result-show-all { display: block; float: left; font-weight: normal; margin: 29px 0; position: relative; text-align: left; width: 60%; }
.yoss-result .yoss-result-left { float: left; width: 75%; }
.yoss-result .yoss-result-right { float: right; width: 25%; }
.yoss-result .product-image { float: left; height: 48px; margin: 20px 4% 30px; overflow: hidden; width: 20%; }
.yoss-result .product-name { display: block; float: left; font-size: 14px; font-weight: bold; line-height: 18px; margin: 16px 0 10px; position: relative; text-align: left; width: 72%; }
.yoss-result .product-brand, .yoss-result .product-category { color: #777; float: left; font-size: 0.8em; margin: 0 10px 0 0; position: relative; }
.yoss-result .product-brand a , .yoss-result .product-category a { color: #777; }
.yoss-result .product-price { display: block; float: left; font-size: 1.6em; font-weight: bold; margin: 20px 0 10px; text-align: center; width: 100%; }
.yoss-result .product-link { border: 1px solid; border-radius: 4px; display: inline-block; font-weight: bold; height: 26px; line-height: 26px; overflow: hidden; text-align: center; text-decoration: none; width: 85%; }',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
);