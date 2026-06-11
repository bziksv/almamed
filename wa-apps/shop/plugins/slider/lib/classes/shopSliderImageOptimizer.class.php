<?php

class shopSliderImageOptimizer
{
    const JPEG_QUALITY = 85;
    const PNG_QUALITY = 8;

    protected static $max_width = array(
        'img' => 1300,
        'img_tablet' => 992,
        'img_mobile' => 576,
    );

    public static function saveUploaded($tmp_path, $target_path, $field)
    {
        $image = waImage::factory($tmp_path);
        $max_width = ifset(self::$max_width, $field, 1300);

        if ($max_width && $image->width > $max_width) {
            $height = (int) round($image->height * ($max_width / $image->width));
            $image->resize($max_width, $height);
        }

        $extension = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        if ($extension === 'png') {
            $image->save($target_path, self::PNG_QUALITY);
        } else {
            $image->save($target_path, self::JPEG_QUALITY);
        }

        return $target_path;
    }
}
