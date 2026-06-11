<?php

class shopSliderPlugin extends shopPlugin
{
    static public function slider()
    {
        $model = new shopSliderModel();
        $records = $model->order('sort ASC')->fetchAll();
        foreach ($records as &$slide) {
            $slide['img_mobile'] = self::getSlideMobileImage(ifset($slide, 'img', ''));
        }
        unset($slide);

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $view = wa()->getView();
        $view->assign('slides', $records);

        return $view->fetch($app_config->getPluginPath('slider').'/templates/frontendSlider.html');
    }

    /**
     * Mobile banner: sm_* thumbnail generated on upload (576px wide).
     */
    protected static function getSlideMobileImage($img)
    {
        if (!$img) {
            return '';
        }
        $basename = basename($img);
        $mobile_path = wa()->getConfig()->getPath('data').'/public/shop/slider/img/sm_'.$basename;
        if (file_exists($mobile_path)) {
            return '/wa-data/public/shop/slider/img/sm_'.$basename;
        }

        return $img;
    }
}
