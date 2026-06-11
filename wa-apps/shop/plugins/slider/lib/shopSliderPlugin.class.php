<?php

class shopSliderPlugin extends shopPlugin
{
    const MAX_VISIBLE_SLIDES = 7;

    static public function slider()
    {
        $model = new shopSliderModel();
        $records = array();
        foreach ($model->order('sort ASC')->fetchAll() as $slide) {
            if (!self::isSlideVisible($slide)) {
                continue;
            }
            $records[] = self::resolveSlideImages($slide);
        }

        if (!$records) {
            return '';
        }

        $records = self::pickSlidesForDisplay($records);

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $view = wa()->getView();
        $view->assign('slides', $records);
        $view->assign('stat_url', json_encode(wa()->getRouteUrl('shop/frontend/stat', array('plugin' => 'slider'), true)));

        return $view->fetch($app_config->getPluginPath('slider').'/templates/frontendSlider.html');
    }

    public static function isSlideVisible(array $slide)
    {
        if (empty($slide['enabled'])) {
            return false;
        }

        $now = time();

        if (!empty($slide['date_from'])) {
            $from = strtotime($slide['date_from'] . ' 00:00:00');
            if ($from && $now < $from) {
                return false;
            }
        }

        if (!empty($slide['date_to'])) {
            $to = strtotime($slide['date_to'] . ' 23:59:59');
            if ($to && $now > $to) {
                return false;
            }
        }

        return true;
    }

    public static function pickSlidesForDisplay(array $slides, $limit = null)
    {
        if ($limit === null) {
            $limit = self::MAX_VISIBLE_SLIDES;
        }

        if (count($slides) <= $limit) {
            return $slides;
        }

        usort($slides, function ($a, $b) {
            $views_a = (int) ifset($a, 'views_count', 0);
            $views_b = (int) ifset($b, 'views_count', 0);
            if ($views_a !== $views_b) {
                return $views_a - $views_b;
            }

            $sort_a = (int) ifset($a, 'sort', 0);
            $sort_b = (int) ifset($b, 'sort', 0);
            if ($sort_a !== $sort_b) {
                return $sort_a - $sort_b;
            }

            return (int) ifset($a, 'id', 0) - (int) ifset($b, 'id', 0);
        });

        $selected = array_slice($slides, 0, $limit);

        usort($selected, function ($a, $b) {
            $sort_a = (int) ifset($a, 'sort', 0);
            $sort_b = (int) ifset($b, 'sort', 0);
            if ($sort_a !== $sort_b) {
                return $sort_a - $sort_b;
            }

            return (int) ifset($a, 'id', 0) - (int) ifset($b, 'id', 0);
        });

        return $selected;
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
