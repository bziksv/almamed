<?php

class shopXmlPluginSettingsAction extends waViewAction
{

    public function execute()
    {
    $plugin = wa('shop')->getPlugin('xml');



    unlink($_SERVER['DOCUMENT_ROOT'].'/wa-cache/apps/webasyst/cache/app_settings/shop.php');

    $brands = new shopProductbrandsPlugin();
    $cat_parent = $plugin->getFullTree(false,true,array(),true);

    $settings = $plugin->getSettings();


        $arrResult = array();
        foreach($settings as $key => $set){
            if(!strstr($key, 'update_time')){
                $arrResult[$key] = $set;
            }
        }

        $cmd_path = wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR ."cli.php shop XmlUpdate";
        $this->view->assign('cmd_path',$cmd_path);

    $this->view->assign('delete_all', $arrResult);

    $this->view->assign('cat', $cat_parent);
    $this->view->assign('brand', $brands->getBrands());

    }

    public function saveSettings($settings = array()) {
        parent::saveSettings($settings);
    }







}