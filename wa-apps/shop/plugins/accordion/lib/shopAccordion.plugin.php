<?php

/**
 * @author wa-apps.ru <info@wa-apps.ru>
 * @copyright 2013-2015 wa-apps.ru
 * @license Webasyst License http://www.webasyst.ru/terms/#eula
 * @link http://www.webasyst.ru/store/plugin/shop/productbrands/
 */
class shopAccordionPlugin extends shopPlugin
{


    static public function getStaticUrlPlugin(){
        return wa()->getAppStaticUrl('shop', false).'plugins/accordion/';
    }


    public static function getAcordionListPage(){
        $plugin = wa('shop')->getPlugin('accordion');
        $settings = $plugin->getSettings();

        $view = wa()->getView();
        $view->assign('settings', $settings);
        $html = $view->fetch($_SERVER['DOCUMENT_ROOT'].self::getStaticUrlPlugin().'templates/actions/Frontend.html');
        return $html;
    }

    public function saveSettings($settings = array()) {
        parent::saveSettings($settings);
    }



}

