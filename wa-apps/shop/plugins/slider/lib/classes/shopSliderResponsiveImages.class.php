<?php

class shopSliderResponsiveImages
{
    const TABLET_WIDTH = 992;
    const MOBILE_WIDTH = 576;

    public static function imgDir()
    {
        return wa()->getConfig()->getPath('data') . '/public/shop/slider/img';
    }

    public static function tabletFilesystemPath($desktop_files_root)
    {
        return str_replace('/img/', '/img/tb_', $desktop_files_root);
    }

    public static function mobileFilesystemPath($desktop_files_root)
    {
        return str_replace('/img/', '/img/sm_', $desktop_files_root);
    }

    public static function publicUrlFromFilesystem($files_root)
    {
        $data_root = wa()->getConfig()->getPath('data');
        if (strpos($files_root, $data_root) !== 0) {
            return '';
        }

        return '/wa-data' . substr($files_root, strlen($data_root));
    }

    public static function desktopFilesystemPath($public_path)
    {
        if (!$public_path) {
            return '';
        }

        return self::imgDir() . '/' . basename($public_path);
    }

    /**
     * @return array{img_tablet: string, img_mobile: string}
     */
    public static function generateFromDesktop($public_path, $force = false)
    {
        $result = array(
            'img_tablet' => '',
            'img_mobile' => '',
        );

        $desktop_path = self::desktopFilesystemPath($public_path);
        if (!$desktop_path || !file_exists($desktop_path)) {
            return $result;
        }

        waFiles::create(self::imgDir());

        $tablet_path = self::tabletFilesystemPath($desktop_path);
        if ($force || !file_exists($tablet_path)) {
            $image_tb = waImage::factory($desktop_path);
            $image_tb->resize(self::TABLET_WIDTH, 400, 'WIDTH');
            $image_tb->save($tablet_path, 100);
        }
        $result['img_tablet'] = self::publicUrlFromFilesystem($tablet_path);

        $mobile_path = self::mobileFilesystemPath($desktop_path);
        if ($force || !file_exists($mobile_path)) {
            $image_sm = waImage::factory($desktop_path);
            $image_sm->resize(self::MOBILE_WIDTH, 220, 'WIDTH');
            $image_sm->save($mobile_path, 100);
        }
        $result['img_mobile'] = self::publicUrlFromFilesystem($mobile_path);

        return $result;
    }

    /**
     * @return array{processed: int, updated: int, skipped: int}
     */
    public static function generateAllSlides($force = false)
    {
        $model = new shopSliderModel();
        $stats = array(
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
        );

        foreach ($model->order('sort ASC')->fetchAll() as $slide) {
            $desktop = ifset($slide, 'img', '');
            if (!$desktop) {
                continue;
            }

            $stats['processed']++;

            $has_tablet = !empty($slide['img_tablet']) && file_exists(self::desktopFilesystemPath($slide['img_tablet']));
            $has_mobile = !empty($slide['img_mobile']) && file_exists(self::desktopFilesystemPath($slide['img_mobile']));

            if (!$force && $has_tablet && $has_mobile) {
                $stats['skipped']++;
                continue;
            }

            $generated = self::generateFromDesktop($desktop, $force);
            $update = array();

            if ($force || empty($slide['img_tablet']) || !$has_tablet) {
                $update['img_tablet'] = $generated['img_tablet'];
            }
            if ($force || empty($slide['img_mobile']) || !$has_mobile) {
                $update['img_mobile'] = $generated['img_mobile'];
            }

            if ($update) {
                $model->updateById($slide['id'], $update);
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }
}
