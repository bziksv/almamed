<?php


/**
 * Class shopCartsPluginSpecialofferHelper
 * Поддержка цен плагина
 * https://www.webasyst.ru/store/plugin/shop/specialoffer/
 */
class shopCartsPluginSpecialofferHelper
{
    protected static function getPlugin()
    {
        if(!class_exists('shopSpecialofferPlugin')) {
            return false;
        }

        try {
            $plugin = wa('shop')->getPlugin('specialoffer');
            return $plugin;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * @param array $item
     */
    public static function prepareItem(&$item)
    {
        if(!$plugin = self::getPlugin()) {
            return;
        }

        $product = new shopProduct($item['product_id']);
        $display = false;
        shopSpecialofferPlugin::product($product, $display);

        if($product->compare_price > $product->price) {
            $item['compare_price'] = $product->compare_price;
            $item['price'] = $product->price;
        }
    }

}