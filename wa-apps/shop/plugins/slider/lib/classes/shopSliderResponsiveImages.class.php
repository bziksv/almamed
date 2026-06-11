<?php

class shopSliderResponsiveImages
{
    const TABLET_WIDTH = 992;
    const MOBILE_WIDTH = 576;
    const WIDE_STRIP_RATIO = 3.0;
    const MOBILE_COLUMNS = 3;

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
            self::generateTablet($desktop_path, $tablet_path);
        }
        $result['img_tablet'] = self::publicUrlFromFilesystem($tablet_path);

        $mobile_path = self::mobileFilesystemPath($desktop_path);
        if ($force || !file_exists($mobile_path)) {
            self::generateMobile($desktop_path, $mobile_path);
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

    protected static function generateTablet($source_path, $target_path)
    {
        $info = self::getImageInfo($source_path);
        if (!$info) {
            return;
        }

        if ($info['width'] / $info['height'] >= self::WIDE_STRIP_RATIO) {
            $columns = self::detectStackColumns($source_path, $info);
            if ($columns > 1) {
                self::generateStackedColumns($source_path, $target_path, self::TABLET_WIDTH, min($columns, 2));
                return;
            }
            self::generateCenterCrop($source_path, $target_path, self::TABLET_WIDTH, 0.85);
            return;
        }

        self::saveResized($source_path, $target_path, self::TABLET_WIDTH);
    }

    protected static function generateMobile($source_path, $target_path)
    {
        $info = self::getImageInfo($source_path);
        if (!$info) {
            return;
        }

        if ($info['width'] / $info['height'] >= self::WIDE_STRIP_RATIO) {
            $columns = self::detectStackColumns($source_path, $info);
            if ($columns > 1) {
                self::generateStackedColumns($source_path, $target_path, self::MOBILE_WIDTH, $columns);
                return;
            }
            self::generateCenterCrop($source_path, $target_path, self::MOBILE_WIDTH, 0.78);
            return;
        }

        self::saveResized($source_path, $target_path, self::MOBILE_WIDTH);
    }

    /**
     * 3 equal columns = three products in a row.
     * 2 columns = one product with images on the sides.
     */
    protected static function detectStackColumns($source_path, array $info)
    {
        $source = self::loadImage($source_path, $info['type']);
        if (!$source) {
            return self::MOBILE_COLUMNS;
        }

        $src_w = $info['width'];
        $src_h = $info['height'];
        $third = (int) floor($src_w / 3);
        $fills = array();

        for ($i = 0; $i < 3; $i++) {
            $fills[$i] = self::regionContentDensity($source, $i * $third, $third, $src_h, 0.0, 1.0);
        }

        imagedestroy($source);

        if ($fills[1] > 0 && ($fills[2] / $fills[1]) < 0.58) {
            return 3;
        }

        if ($fills[0] >= 0.5 && $fills[2] >= 0.5) {
            return 2;
        }

        return 0;
    }

    protected static function regionContentDensity($source, $column_x, $column_w, $height, $start_ratio, $end_ratio)
    {
        $step = 4;
        $content = 0;
        $total = 0;
        $x_start = $column_x + (int) round($column_w * $start_ratio);
        $x_end = $column_x + (int) round($column_w * $end_ratio);

        for ($py = 0; $py < $height; $py += $step) {
            for ($px = $x_start; $px < $x_end; $px += $step) {
                $rgb = imagecolorat($source, $px, $py);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if (!self::isBackgroundPixel($r, $g, $b)) {
                    $content++;
                }
                $total++;
            }
        }

        return $total ? ($content / $total) : 0;
    }

    protected static function isBackgroundPixel($r, $g, $b)
    {
        return $r > 230 && $g > 235 && $b > 240;
    }

    protected static function generateCenterCrop($source_path, $target_path, $target_width, $crop_ratio)
    {
        $info = self::getImageInfo($source_path);
        if (!$info) {
            return;
        }

        $source = self::loadImage($source_path, $info['type']);
        if (!$source) {
            return;
        }

        $src_w = $info['width'];
        $src_h = $info['height'];
        $crop_w = max(1, (int) round($src_w * $crop_ratio));
        $crop_x = max(0, (int) floor(($src_w - $crop_w) / 2));
        $scale = $target_width / $crop_w;
        $target_h = max(1, (int) round($src_h * $scale));

        $canvas = imagecreatetruecolor($target_width, $target_h);
        self::fillBackground($canvas, $info['type']);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $crop_x,
            0,
            $target_width,
            $target_h,
            $crop_w,
            $src_h
        );

        self::saveImage($canvas, $target_path, $info['type']);
        imagedestroy($source);
        imagedestroy($canvas);
    }

    /**
     * Wide banner: split into columns and stack vertically so text stays readable.
     */
    protected static function generateStackedColumns($source_path, $target_path, $target_width, $columns)
    {
        $info = self::getImageInfo($source_path);
        if (!$info || $columns < 1) {
            return;
        }

        $source = self::loadImage($source_path, $info['type']);
        if (!$source) {
            return;
        }

        $src_w = $info['width'];
        $src_h = $info['height'];

        if ($columns === 2) {
            $segments = array(
                array(0, (int) floor($src_w / 2)),
                array((int) floor($src_w / 2), (int) ceil($src_w / 2)),
            );
        } else {
            $col_w = (int) floor($src_w / $columns);
            $segments = array();
            for ($i = 0; $i < $columns; $i++) {
                $segments[] = array($i * $col_w, $col_w);
            }
        }

        $panel_heights = array();
        foreach ($segments as $segment) {
            list($x, $slice_w) = $segment;
            $scale = $target_width / $slice_w;
            $panel_heights[] = max(1, (int) round($src_h * $scale));
        }
        $total_h = array_sum($panel_heights);

        $canvas = imagecreatetruecolor($target_width, $total_h);
        self::fillBackground($canvas, $info['type']);

        $offset_y = 0;
        foreach ($segments as $index => $segment) {
            list($x, $slice_w) = $segment;
            $panel_h = $panel_heights[$index];

            $slice = imagecreatetruecolor($slice_w, $src_h);
            self::fillBackground($slice, $info['type']);
            imagecopy($slice, $source, 0, 0, $x, 0, $slice_w, $src_h);

            $scaled = imagecreatetruecolor($target_width, $panel_h);
            self::fillBackground($scaled, $info['type']);
            imagecopyresampled(
                $scaled,
                $slice,
                0,
                0,
                0,
                0,
                $target_width,
                $panel_h,
                $slice_w,
                $src_h
            );

            imagecopy($canvas, $scaled, 0, $offset_y, 0, 0, $target_width, $panel_h);
            $offset_y += $panel_h;

            imagedestroy($slice);
            imagedestroy($scaled);
        }

        self::saveImage($canvas, $target_path, $info['type']);
        imagedestroy($source);
        imagedestroy($canvas);
    }

    protected static function saveResized($source_path, $target_path, $max_width)
    {
        $info = self::getImageInfo($source_path);
        if (!$info) {
            return;
        }

        $source = self::loadImage($source_path, $info['type']);
        if (!$source) {
            return;
        }

        $src_w = $info['width'];
        $src_h = $info['height'];
        if ($src_w <= $max_width) {
            $new_w = $src_w;
            $new_h = $src_h;
        } else {
            $new_w = $max_width;
            $new_h = max(1, (int) round($src_h * ($max_width / $src_w)));
        }

        $target = imagecreatetruecolor($new_w, $new_h);
        self::fillBackground($target, $info['type']);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);

        self::saveImage($target, $target_path, $info['type']);
        imagedestroy($source);
        imagedestroy($target);
    }

    protected static function getImageInfo($path)
    {
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        return array(
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
        );
    }

    protected static function loadImage($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            default:
                return null;
        }
    }

    protected static function fillBackground($image, $type)
    {
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
            return;
        }

        $white = imagecolorallocate($image, 245, 248, 252);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $white);
    }

    protected static function saveImage($image, $path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $path, 92);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $path, 8);
                break;
        }
    }
}
