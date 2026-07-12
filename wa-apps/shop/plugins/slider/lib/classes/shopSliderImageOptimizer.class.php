<?php

class shopSliderImageOptimizer
{
    const PNG_QUALITY = 8;

    protected static $max_width = array(
        'img' => shopSliderResponsiveImages::DESKTOP_WIDTH,
        'img_tablet' => shopSliderResponsiveImages::TABLET_WIDTH,
        'img_mobile' => shopSliderResponsiveImages::MOBILE_WIDTH,
    );

    public static function saveUploaded($tmp_path, $target_path, $field)
    {
        $image = waImage::factory($tmp_path);
        $max_width = ifset(self::$max_width, $field, shopSliderResponsiveImages::DESKTOP_WIDTH);

        if ($max_width && $image->width > $max_width) {
            $height = (int) round($image->height * ($max_width / $image->width));
            $image->resize($max_width, $height);
        }

        $extension = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        if ($extension === 'png') {
            $image->save($target_path, self::PNG_QUALITY);
        } else {
            $image->save($target_path, shopSliderResponsiveImages::JPEG_QUALITY);
        }

        return $target_path;
    }
}
