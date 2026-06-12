<?php

class shopWebpimagesHelper
{
    const WEBP_QUALITY = 85;

    /**
     * @return shopViewHelper
     */
    protected static function shopHelper()
    {
        return new shopViewHelper(wa('shop'));
    }

    /**
     * @return string
     */
    public static function productImgHtml($product, $size, $attributes = array())
    {
        $html = self::shopHelper()->productImgHtml($product, $size, $attributes);
        if (!$html || empty($product['image_id'])) {
            return $html;
        }

        $image = array(
            'id' => $product['image_id'],
            'product_id' => ifset($product, 'id', ifset($product, 'product_id', 0)),
            'filename' => ifset($product, 'image_filename'),
            'ext' => ifset($product, 'ext', 'jpg'),
        );

        return self::wrapPicture($html, self::webpUrl($image, $size));
    }

    /**
     * @return string
     */
    public static function imgHtml($image, $size, $attributes = array())
    {
        $html = self::shopHelper()->imgHtml($image, $size, $attributes);
        if (!$html || empty($image['id'])) {
            return $html;
        }

        return self::wrapPicture($html, self::webpUrl($image, $size));
    }

    /**
     * @return string
     */
    public static function webpUrl(array $image, $size)
    {
        if (empty($image['id']) || empty($image['product_id'])) {
            return '';
        }

        $thumb_path = shopImage::getThumbsPath($image, $size);
        if (!file_exists($thumb_path)) {
            shopImage::generateThumbs($image, array($size));
        }
        if (!file_exists($thumb_path)) {
            return '';
        }

        if (!self::ensureWebp($thumb_path)) {
            return '';
        }

        $url = shopImage::getUrl($image, $size);

        return preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url);
    }

    /**
     * @return array{processed: int, created: int, skipped: int}
     */
    public static function generateAll($force = false, array $size_pattern = null)
    {
        if ($size_pattern === null) {
            $size_pattern = array('200', '750', '96x96', '970');
        }

        $stats = array(
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
        );

        $root = wa()->getDataPath('products/', true, 'shop', false);
        if (!is_dir($root)) {
            return $stats;
        }

        $pattern = '/\.(' . implode('|', array_map('preg_quote', $size_pattern)) . ')\.(jpe?g|png)$/i';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (!preg_match($pattern, $path)) {
                continue;
            }

            $stats['processed']++;
            $webp_path = self::webpPath($path);

            if (!$force && file_exists($webp_path) && filemtime($webp_path) >= filemtime($path)) {
                $stats['skipped']++;
                continue;
            }

            if (self::ensureWebp($path, $force)) {
                $stats['created']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    protected static function wrapPicture($html, $webp_url)
    {
        if (!$webp_url) {
            return $html;
        }

        return '<picture><source srcset="' . htmlspecialchars($webp_url, ENT_QUOTES, 'UTF-8') . '" type="image/webp">' . $html . '</picture>';
    }

    protected static function webpPath($path)
    {
        return preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    }

    /**
     * @return string|false
     */
    protected static function ensureWebp($source_path, $force = false)
    {
        if (!function_exists('imagewebp') || !$source_path || !file_exists($source_path)) {
            return false;
        }

        $webp_path = self::webpPath($source_path);
        if (!$force && file_exists($webp_path) && filemtime($webp_path) >= filemtime($source_path)) {
            return $webp_path;
        }

        $info = @getimagesize($source_path);
        if (!$info) {
            return false;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($source_path);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        imagewebp($image, $webp_path, self::WEBP_QUALITY);
        imagedestroy($image);

        return file_exists($webp_path) ? $webp_path : false;
    }
}
