<?php

class shopSliderPlugin extends shopPlugin
{


    static public function slider(){

        $model = new shopSliderModel();
        $records = $model->order('sort ASC')->fetchAll();
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $view = wa()->getView();
		
        $view->assign('slides', $records);
        return $view->fetch($app_config->getPluginPath('slider').'/templates/frontendSlider.html');

    }


}



