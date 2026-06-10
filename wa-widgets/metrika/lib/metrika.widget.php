<?php

/**
 * Class metrikaWidget
 * Статистика посещения сайта за 1 день, 7 дней, 30 дней
 */
class metrikaWidget extends waWidget
{

    public function defaultAction()
    {

        $settings = $this->getSettings();

        $token = $this->getToken($settings);

        if (isset($token->error)) {
            $this->showError();
        } else {
            $this->showStat($settings, $token->access_token);
        }
    }

    /**
     * Выводим статистику метрики
     * @param $settings
     * @param $token
     */
    protected function showStat($settings, $token)
    {
        // Название сайта по ID счетчика
        $site = $this->getSitename($settings['number'], $token);

        //  Статистика
        $metrika = $this->getStat($settings['number'], $settings['period'], $token);

        $this->display(array(
            'site' => $site->counter->site,
            'visits' => $metrika->totals->visits,
            'views' => $metrika->totals->page_views,
            'color' => $settings['color'],
            'period' => $settings['period'],
            'info' => $this->getInfo()
        ));
    }

    /**
     * Выводим ошибку подключения
     */
    protected function showError()
    {
        $message = 'Ошибка подключения к Яндекс.Метрике. Неверно указанные данные - логин, пароль, OAuth ID или OAuth Пароль.';
        $this->display(array(
            'message' => $message,
            'info' => $this->getInfo()
        ));
    }

    /**
     * Статистика посещений
     * @param $id
     * @param $period
     * @param $token
     * @return string
     * @throws waException
     */
    protected function getStat($id, $period, $token)
    {
        if ($period == 7) {
            $date_end = date("Ymd", strtotime('-7 day'));
        } elseif ($period == 30) {
            $date_end = date("Ymd", strtotime('-30 day'));
        } else {
            $date_end = date("Ymd");
        }
        $metrika = $this->load(
            'https://api-metrika.yandex.ru/stat/traffic/summary.json?id=' . $id . '&pretty=1&date1=' . $date_end . '&date2=' . date("Ymd") . '&oauth_token=' . $token
        );

        return $metrika;
    }

    /**
     * Название сайта по ID
     * @param $site_id
     * @param $token
     * @return string
     * @throws waException
     */
    protected function getSitename($id, $token)
    {
        $site = $this->load(
            'https://api-metrika.yandex.ru/counter/' . $id . '.json?pretty=1&oauth_token=' . $token
        );
        return $site;
    }

    /**
     * Получение токена
     * @param $settings
     * @return string
     * @throws waException
     */
    protected function getToken($settings)
    {
        $token = $this->load(
            'https://oauth.yandex.ru/token',
            'grant_type=password&username=' . $settings['login'] . '&password=' . $settings['password'] . '&client_id=' . $settings['client_id'] . '&client_secret=' . $settings['client_secret']
        );
        return $token;
    }

    /**
     * @param string $url
     * @return string
     * @throws waException
     */
    protected function load($url, $post = null)
    {
        if (!extension_loaded('curl')) {
            if (ini_get('allow_url_fopen')) {
                $default_socket_timeout = @ini_set('default_socket_timeout', 10);
                $result = file_get_contents($url);
                @ini_set('default_socket_timeout', $default_socket_timeout);
            } else {
                throw new waException('Curl extension not loaded');
            }
        } else {

            if (!function_exists('curl_init') || !($ch = curl_init())) {
                throw new waException("Can't init curl");
            }

            if (curl_errno($ch) != 0) {
                $error = "Can't init curl";
                $error .= ": " . curl_errno($ch) . " - " . curl_error($ch);
                throw new waException($error);
            }
            @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            @curl_setopt($ch, CURLOPT_HEADER, 0);
            @curl_setopt($ch, CURLOPT_URL, $url);
            if ($post) {
                @curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }

            $result = @curl_exec($ch);
            if (curl_errno($ch) != 0) {
                $result = '';
            }

            curl_close($ch);
        }

        return json_decode($result);
    }

}