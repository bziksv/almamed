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

    /**
     * Preload tags for the first visible slider image (homepage LCP).
     */
    public static function preloadLcp()
    {
        $first = self::getFirstVisibleSlide();
        if (!$first) {
            return '';
        }

        $links = array();
        $mobile = ifset($first, 'img_mobile_webp', '') ?: ifset($first, 'img_mobile', '');
        $tablet = ifset($first, 'img_tablet_webp', '') ?: ifset($first, 'img_tablet', '');
        $desktop = ifset($first, 'img_webp', '') ?: ifset($first, 'img', '');
        $mobile_w = (int) ifset($first, 'img_mobile_w', 0);
        $tablet_w = (int) ifset($first, 'img_tablet_w', 0);
        $desktop_w = (int) ifset($first, 'img_w', 0);

        if ($mobile) {
            $links[] = self::preloadLink($mobile, '(max-width: 480px)', $mobile_w);
        }
        if ($tablet) {
            $links[] = self::preloadLink($tablet, '(max-width: 1200px) and (min-width: 481px)', $tablet_w);
        }
        if ($desktop) {
            $links[] = self::preloadLink($desktop, '(min-width: 1201px)', $desktop_w);
        }

        return implode("\n    ", $links);
    }

    protected static function preloadLink($url, $media, $width = 0)
    {
        $type = preg_match('/\.webp(\?|$)/i', $url) ? ' type="image/webp"' : '';
        $srcset = $width > 0
            ? sprintf(' imagesrcset="%s %dw"', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), $width)
            : '';

        return sprintf(
            '<link rel="preload" as="image" href="%s"%s%s media="%s" fetchpriority="high">',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $type,
            $srcset,
            htmlspecialchars($media, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @return array|null
     */
    protected static function getFirstVisibleSlide()
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
            return null;
        }

        $records = self::pickSlidesForDisplay($records);

        return reset($records) ?: null;
    }

    public static function isSlideVisible(array $slide)
    {
        if (array_key_exists('enabled', $slide) && !$slide['enabled']) {
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

    /**
     * @event backend_menu
     * @return array
     */
    public function backendMenu()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            return array();
        }

        $this->addJs('js/menu-tab.js');

        return array(
            'core_li' => '<li class="no-tab slider-topmenu-li">'
                .'<a href="?action=plugins#/slider/">'
                .'<img src="'.$this->getPluginStaticUrl().'img/brands.png" alt="" style="width:16px;height:16px;vertical-align:-3px;margin-right:2px;"> '
                .'Слайдер'
                .'</a></li>',
        );
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
        $slide['img_webp'] = shopSliderResponsiveImages::publicWebpUrl($desktop);
        $slide['img_tablet_webp'] = shopSliderResponsiveImages::publicWebpUrl($tablet);
        $slide['img_mobile_webp'] = shopSliderResponsiveImages::publicWebpUrl($mobile);

        $desktop_dims = shopSliderResponsiveImages::publicImageDimensions($desktop);
        $tablet_dims = shopSliderResponsiveImages::publicImageDimensions($tablet);
        $mobile_dims = shopSliderResponsiveImages::publicImageDimensions($mobile);
        $slide['img_w'] = $desktop_dims['width'];
        $slide['img_h'] = $desktop_dims['height'];
        $slide['img_tablet_w'] = $tablet_dims['width'] ?: $desktop_dims['width'];
        $slide['img_tablet_h'] = $tablet_dims['height'] ?: $desktop_dims['height'];
        $slide['img_mobile_w'] = $mobile_dims['width'] ?: $desktop_dims['width'];
        $slide['img_mobile_h'] = $mobile_dims['height'] ?: $desktop_dims['height'];

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
