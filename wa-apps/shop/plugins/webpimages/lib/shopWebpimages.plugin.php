<?php

class shopWebpimagesPlugin extends shopPlugin
{
    public static function productImgHtml($product, $size, $attributes = array())
    {
        return shopWebpimagesHelper::productImgHtml($product, $size, $attributes);
    }

    public static function imgHtml($image, $size, $attributes = array())
    {
        return shopWebpimagesHelper::imgHtml($image, $size, $attributes);
    }
}
