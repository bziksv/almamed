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

        $image = self::normalizeImage(array(
            'id' => $product['image_id'],
            'product_id' => ifset($product, 'id', ifset($product, 'product_id', 0)),
            'filename' => ifset($product, 'image_filename', ''),
            'ext' => ifset($product, 'ext', 'jpg'),
        ));

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

        return self::wrapPicture($html, self::webpUrl(self::normalizeImage($image), $size));
    }

    /**
     * @return string
     */
    public static function webpUrl(array $image, $size)
    {
        $image = self::normalizeImage($image);
        if (empty($image['id']) || empty($image['product_id'])) {
            return '';
        }

        $original_path = shopImage::getPath($image);
        if (!is_readable($original_path)) {
            return '';
        }

        $thumb_path = shopImage::getThumbsPath($image, $size);
        if (!file_exists($thumb_path)) {
            try {
                shopImage::generateThumbs($image, array($size));
            } catch (Exception $e) {
                return '';
            }
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
     * @param int $progress_every выводить прогресс каждые N файлов (0 — без вывода)
     * @return array{processed: int, created: int, skipped: int}
     */
    public static function generateAll($force = false, array $size_pattern = null, $progress_every = 0)
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

        $started_at = microtime(true);
        if ($progress_every > 0) {
            self::reportProgress($stats, $started_at, true);
        }

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
            } elseif (self::ensureWebp($path, $force)) {
                $stats['created']++;
            } else {
                $stats['skipped']++;
            }

            if ($progress_every > 0 && $stats['processed'] % $progress_every === 0) {
                self::reportProgress($stats, $started_at, false);
            }
        }

        if ($progress_every > 0 && $stats['processed'] > 0) {
            self::reportProgress($stats, $started_at, false, true);
        }

        return $stats;
    }

    protected static function reportProgress(array $stats, $started_at, $is_start = false, $is_final = false)
    {
        $elapsed = max(microtime(true) - $started_at, 0.001);
        $rate = $stats['processed'] / $elapsed;

        if ($is_start) {
            echo "Product thumbs WebP — scan started at " . date('Y-m-d H:i:s') . "\n";
        } elseif ($is_final) {
            echo sprintf(
                "[%s] done — processed=%d created=%d skipped=%d, elapsed=%ds (avg %.1f/s)\n",
                date('H:i:s'),
                $stats['processed'],
                $stats['created'],
                $stats['skipped'],
                (int) round($elapsed),
                $rate
            );
        } else {
            echo sprintf(
                "[%s] processed=%d created=%d skipped=%d (%.1f/s)\n",
                date('H:i:s'),
                $stats['processed'],
                $stats['created'],
                $stats['skipped'],
                $rate
            );
        }

        if (defined('STDOUT')) {
            @fflush(STDOUT);
        }
    }

    protected static function normalizeImage(array $image)
    {
        $filename = ifset($image, 'filename', ifset($image, 'image_filename', ''));
        if (!is_string($filename)) {
            $filename = '';
        }

        $product_id = ifset($image, 'product_id', ifset($image, 'id', 0));

        return array(
            'id' => ifset($image, 'id', 0),
            'product_id' => $product_id,
            'filename' => $filename,
            'ext' => ifset($image, 'ext', 'jpg'),
        );
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
