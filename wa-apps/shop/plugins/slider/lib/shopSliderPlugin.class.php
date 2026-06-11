<?php

class shopSliderPlugin extends shopPlugin
{
    static public function slider()
    {
        $model = new shopSliderModel();
        $records = $model->order('sort ASC')->fetchAll();
        foreach ($records as &$slide) {
            $slide = self::resolveSlideImages($slide);
        }
        unset($slide);

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $view = wa()->getView();
        $view->assign('slides', $records);

        return $view->fetch($app_config->getPluginPath('slider').'/templates/frontendSlider.html');
    }

    protected static function resolveSlideImages(array $slide)
    {
        $desktop = ifset($slide, 'img', '');
        $tablet = ifset($slide, 'img_tablet', '');
        $mobile = ifset($slide, 'img_mobile', '');

        if (!$tablet) {
            $tablet = self::getAutoTabletImage($desktop) ?: $desktop;
        }
        if (!$mobile) {
            $mobile = self::getAutoMobileImage($desktop) ?: $desktop;
        }

        $slide['img_tablet'] = $tablet;
        $slide['img_mobile'] = $mobile;

        return $slide;
    }

    protected static function getAutoTabletImage($img)
    {
        if (!$img) {
            return '';
        }
        $basename = basename($img);
        $tablet_path = wa()->getConfig()->getPath('data').'/public/shop/slider/img/tb_'.$basename;
        if (file_exists($tablet_path)) {
            return '/wa-data/public/shop/slider/img/tb_'.$basename;
        }

        return '';
    }

    protected static function getAutoMobileImage($img)
    {
        if (!$img) {
            return '';
        }
        $basename = basename($img);
        $mobile_path = wa()->getConfig()->getPath('data').'/public/shop/slider/img/sm_'.$basename;
        if (file_exists($mobile_path)) {
            return '/wa-data/public/shop/slider/img/sm_'.$basename;
        }

        return '';
    }
}
