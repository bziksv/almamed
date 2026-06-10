<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 21.08.14
 * Time: 23:07
 */

return array(
    'categories'        => array(
        'title'         => _wp('Users categories'),
        'description'   => _wp('Select categories of users to display vendors links to the frontend.'),
        'control_type'  => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopVendorlinkPlugin', 'getUserCategories'),
    ),
);
