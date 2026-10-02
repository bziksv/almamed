<?php

/**
 * Gate for /search/…?sort=… — same idea as kosmamed km_catalog_gate.
 *
 * Direct hit with sort (no same-site Referer, no cookie) → image captcha.
 * Search bots / valid cookie / same-site Referer → pass through.
 */
class shopSearchGate
{
    const COOKIE = 'am_sg';
    const TTL = 1209600; // 14 days
    const POST_FLAG = 'am_search_gate';
    const STORAGE_KEY = 'am_search_gate_captcha';

    public static function run()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $method = strtoupper(ifset($_SERVER['REQUEST_METHOD'], 'GET'));
        if ($method !== 'GET' && $method !== 'HEAD' && $method !== 'POST') {
            return;
        }

        if (!self::isTailedSearch()) {
            return;
        }
        if (self::isSearchBot(ifset($_SERVER['HTTP_USER_AGENT'], ''))) {
            return;
        }
        if (self::cookieValid()) {
            return;
        }
        if (self::sameSiteReferer()) {
            self::setCookie();
            return;
        }

        if ($method === 'POST' && (string)waRequest::post(self::POST_FLAG) === '1') {
            $word = (string)waRequest::post('captcha_word');
            if ($word !== '' && self::checkCaptcha($word)) {
                self::setCookie();
                self::pass(ifset($_SERVER['REQUEST_URI'], '/search/'));
            }
            self::show('Неверный код. Введите новый.');
        }

        if ($method === 'HEAD') {
            if (!headers_sent()) {
                header('Cache-Control: no-store');
                http_response_code(200);
            }
            exit;
        }

        self::show();
    }

    protected static function isTailedSearch()
    {
        $uri = (string)ifset($_SERVER['REQUEST_URI'], '');
        $path = (string)parse_url($uri, PHP_URL_PATH);
        if (!preg_match('#^/search(/|$)#', $path)) {
            return false;
        }

        $query = (string)parse_url($uri, PHP_URL_QUERY);
        if ($query === '') {
            return false;
        }

        $params = array();
        parse_str($query, $params);

        return isset($params['sort']);
    }

    protected static function isSearchBot($ua)
    {
        return (bool)preg_match(
            '/Googlebot|Google-InspectionTool|Storebot-Google|AdsBot-Google|YandexBot|YandexImages|YandexMobileBot|YandexWebmaster|YandexMarket|bingbot|Mail\.RU_Bot|Applebot|almamed-warmup|almamed-psi/i',
            $ua
        );
    }

    protected static function sameSiteReferer()
    {
        $ref = (string)ifset($_SERVER['HTTP_REFERER'], '');
        if ($ref === '') {
            return false;
        }
        $host = strtolower((string)parse_url($ref, PHP_URL_HOST));
        $allowed = array(
            'almamed.su',
            'www.almamed.su',
            'localhost',
            '127.0.0.1',
        );

        return in_array($host, $allowed, true);
    }

    protected static function secret()
    {
        static $secret = null;
        if ($secret !== null) {
            return $secret;
        }
        $root = wa()->getConfig()->getRootPath();
        $secret = hash('sha256', 'am-search-gate|' . $root);
        return $secret;
    }

    protected static function cookieValid()
    {
        $raw = (string)waRequest::cookie(self::COOKIE, '');
        $parts = explode('.', $raw, 2);
        if (count($parts) !== 2 || !ctype_digit($parts[0])) {
            return false;
        }
        if ((int)$parts[0] < time()) {
            return false;
        }
        $expected = hash_hmac('sha256', $parts[0], self::secret());
        return hash_equals($expected, $parts[1]);
    }

    protected static function setCookie()
    {
        $expires = time() + self::TTL;
        $value = $expires . '.' . hash_hmac('sha256', (string)$expires, self::secret());
        $secure = waRequest::isHttps();

        // PHP 7.2: no setcookie() options array
        $cookie = self::COOKIE . '=' . rawurlencode($value)
            . '; Expires=' . gmdate('D, d-M-Y H:i:s T', $expires)
            . '; Path=/'
            . '; HttpOnly'
            . '; SameSite=Lax';
        if ($secure) {
            $cookie .= '; Secure';
        }
        if (!headers_sent()) {
            header('Set-Cookie: ' . $cookie, false);
        }
        $_COOKIE[self::COOKIE] = $value;
    }

    protected static function generateCode()
    {
        $chars = 'abdefhknrqstxyz23456789';
        $code = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[mt_rand(0, $max)];
        }
        return $code;
    }

    protected static function storeCaptcha($code)
    {
        wa()->getStorage()->set(self::STORAGE_KEY, strtolower($code));
    }

    protected static function checkCaptcha($word)
    {
        $expected = (string)wa()->getStorage()->get(self::STORAGE_KEY);
        wa()->getStorage()->del(self::STORAGE_KEY);
        if ($expected === '') {
            return false;
        }
        return hash_equals($expected, strtolower(trim($word)));
    }

    protected static function captchaDataUri($code)
    {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }

        $w = 160;
        $h = 48;
        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 241, 245, 249);
        $fg = imagecolorallocate($im, 30, 41, 59);
        $noise = imagecolorallocate($im, 148, 163, 184);
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        for ($i = 0; $i < 40; $i++) {
            imagesetpixel($im, mt_rand(0, $w - 1), mt_rand(0, $h - 1), $noise);
        }
        for ($i = 0; $i < 4; $i++) {
            imageline($im, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $noise);
        }

        $font = 5;
        $tw = imagefontwidth($font) * strlen($code);
        $th = imagefontheight($font);
        $x = (int)(($w - $tw) / 2);
        $y = (int)(($h - $th) / 2);
        imagestring($im, $font, $x, $y, $code, $fg);

        ob_start();
        imagepng($im);
        $png = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    protected static function pass($uri)
    {
        $safe = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store');
            http_response_code(200);
        }
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8">'
            . '<meta http-equiv="refresh" content="0;url=' . $safe . '">'
            . '<title>Открываем поиск</title></head><body>'
            . '<p>Код принят. <a href="' . $safe . '">Перейти к результатам</a></p>'
            . '<script>location.replace(' . json_encode($uri) . ');</script>'
            . '</body></html>';
        exit;
    }

    protected static function show($error = '')
    {
        $code = self::generateCode();
        self::storeCaptcha($code);
        $img = self::captchaDataUri($code);

        $uri = (string)ifset($_SERVER['REQUEST_URI'], '/search/');
        $action = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
        $error_html = $error !== ''
            ? '<p class="am-gate-error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('X-Robots-Tag: noindex, nofollow');
            http_response_code(200);
        }

        $img_html = $img !== ''
            ? '<img src="' . $img . '" width="160" height="48" alt="Код">'
            : '<p><strong>' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</strong></p>';

        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<title>Подтвердите, что вы не робот</title>'
            . '<style>'
            . 'html,body{margin:0;height:100%;background:#f1f5f9;color:#1e293b;font:16px/1.45 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}'
            . 'body{display:flex;align-items:center;justify-content:center;padding:24px;}'
            . '.am-gate{width:100%;max-width:420px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 12px 40px rgba(15,23,42,.08);padding:28px 24px 24px;}'
            . '.am-gate h1{margin:0 0 8px;font-size:22px;line-height:1.3;}'
            . '.am-gate p{margin:0 0 16px;color:#475569;}'
            . '.am-gate-error{color:#b91c1c;font-weight:600;}'
            . '.am-gate img{display:block;margin:0 0 12px;border:1px solid #e2e8f0;border-radius:8px;}'
            . '.am-gate label{display:block;margin:0 0 6px;font-size:14px;color:#475569;}'
            . '.am-gate input[type=text]{width:100%;box-sizing:border-box;height:44px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:18px;letter-spacing:.08em;}'
            . '.am-gate button{margin-top:14px;width:100%;height:46px;border:0;border-radius:8px;background:#0e7490;color:#fff;font-size:16px;font-weight:600;cursor:pointer;}'
            . '.am-gate button:hover{background:#155e75;}'
            . '</style></head><body><div class="am-gate">'
            . '<h1>Подтвердите, что вы не робот</h1>'
            . '<p>Страница поиска открыта сразу с сортировкой. Введите код с картинки, и мы покажем результаты.</p>'
            . $error_html
            . '<form method="post" action="' . $action . '">'
            . '<input type="hidden" name="' . self::POST_FLAG . '" value="1">'
            . $img_html
            . '<label for="am-captcha-word">Код с картинки</label>'
            . '<input id="am-captcha-word" type="text" name="captcha_word" autocomplete="off" required autofocus>'
            . '<button type="submit">Продолжить</button>'
            . '</form></div></body></html>';
        exit;
    }
}
