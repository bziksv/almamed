<?php

/**
 * Static helpers for theme and other plugins.
 */
class shopAbdisplayHelper
{
    const EVENT_VIEW = 'view';
    const EVENT_CART = 'cart';
    const EVENT_LEAD = 'lead';
    const EVENT_ORDER = 'order';

    /** Default experiment keys (scaffold). */
    public static function defaultExperiments()
    {
        return array(
            'product_card' => array(
                'label'     => 'Карточка товара',
                'enabled'   => 0,
                'abtest_id' => 0,
            ),
        );
    }

    /**
     * @return shopAbdisplayPlugin|null
     */
    public static function plugin()
    {
        try {
            $plugins = wa('shop')->getConfig()->getPlugins();
            if (empty($plugins['abdisplay'])) {
                return null;
            }
            return wa('shop')->getPlugin('abdisplay');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    public static function getExperiments()
    {
        $plugin = self::plugin();
        $defaults = self::defaultExperiments();
        if (!$plugin) {
            return $defaults;
        }
        $raw = $plugin->getSettings('experiments_json');
        $decoded = array();
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $decoded = array();
            }
        }
        foreach ($defaults as $key => $def) {
            if (!isset($decoded[$key]) || !is_array($decoded[$key])) {
                $decoded[$key] = $def;
            } else {
                $decoded[$key] = array_merge($def, $decoded[$key]);
                $decoded[$key]['enabled'] = !empty($decoded[$key]['enabled']) ? 1 : 0;
                $decoded[$key]['abtest_id'] = (int) ifset($decoded[$key], 'abtest_id', 0);
                $decoded[$key]['label'] = (string) ifset($decoded[$key], 'label', $def['label']);
            }
        }
        return $decoded;
    }

    /**
     * @param array $experiments
     */
    public static function saveExperiments(array $experiments)
    {
        $plugin = self::plugin();
        if (!$plugin) {
            return;
        }
        $clean = array();
        foreach (self::defaultExperiments() as $key => $def) {
            $row = ifset($experiments, $key, array());
            if (!is_array($row)) {
                $row = array();
            }
            $clean[$key] = array(
                'label'     => (string) ifset($row, 'label', $def['label']),
                'enabled'   => !empty($row['enabled']) ? 1 : 0,
                'abtest_id' => (int) ifset($row, 'abtest_id', 0),
            );
        }
        // allow extra keys later
        foreach ($experiments as $key => $row) {
            if (isset($clean[$key]) || !is_array($row)) {
                continue;
            }
            $key = preg_replace('~[^a-z0-9_]+~', '', strtolower((string) $key));
            if ($key === '') {
                continue;
            }
            $clean[$key] = array(
                'label'     => (string) ifset($row, 'label', $key),
                'enabled'   => !empty($row['enabled']) ? 1 : 0,
                'abtest_id' => (int) ifset($row, 'abtest_id', 0),
            );
        }
        $plugin->saveSettings(array(
            'experiments_json' => json_encode($clean, JSON_UNESCAPED_UNICODE),
        ));
    }

    public static function canForce()
    {
        try {
            $user = wa()->getUser();
        } catch (Exception $e) {
            return false;
        }
        if (!$user || !$user->isAuth()) {
            return false;
        }
        return (int) $user['is_user'] === 1;
    }

    /**
     * Bot / crawler → always control variant A (no random).
     */
    public static function isBot()
    {
        $ua = (string) waRequest::getUserAgent();
        if ($ua === '') {
            return false;
        }
        return (bool) preg_match(
            '~bot|crawl|spider|slurp|facebookexternalhit|preview|yandex\.com/bots|googlebot|bingbot|semrush|ahrefs|dotbot|petalbot|claudebot~i',
            $ua
        );
    }

    /**
     * Resolve variant code for experiment key. Null if disabled / missing.
     *
     * @param string $key
     * @return string|null
     */
    public static function variant($key)
    {
        $key = (string) $key;
        $experiments = self::getExperiments();
        if (empty($experiments[$key]) || empty($experiments[$key]['enabled'])) {
            return null;
        }
        $abtest_id = (int) $experiments[$key]['abtest_id'];
        if ($abtest_id <= 0) {
            return null;
        }

        // Staff force: ?abdisplay_force[product_card]=B or cookie
        if (self::canForce()) {
            $force_get = waRequest::get('abdisplay_force', array(), waRequest::TYPE_ARRAY);
            if (isset($force_get[$key]) && $force_get[$key] !== '') {
                $code = strtoupper(preg_replace('~[^A-Za-z0-9]~', '', (string) $force_get[$key]));
                self::forceCookie($key, $abtest_id, $code);
                return $code !== '' ? $code : null;
            }
            $force_cookie = waRequest::cookie('abdisplay_force_'.$key, '', waRequest::TYPE_STRING_TRIM);
            if ($force_cookie !== '') {
                $code = strtoupper(preg_replace('~[^A-Za-z0-9]~', '', $force_cookie));
                if ($code !== '') {
                    self::ensureWaabtCookie($abtest_id, $code);
                    return $code;
                }
            }
        }

        if (self::isBot()) {
            return self::controlCode($abtest_id);
        }

        $code = self::nativeAbtest($abtest_id);
        return $code ? (string) $code : null;
    }

    /**
     * Same semantics as shopViewHelper::ABtest (cookie waabt{id}).
     *
     * @param int $abtest_id
     * @return string|null
     */
    protected static function nativeAbtest($abtest_id)
    {
        $abtest_id = (int) $abtest_id;
        if ($abtest_id <= 0) {
            return null;
        }
        static $cache = array();
        if (array_key_exists($abtest_id, $cache)) {
            return $cache[$abtest_id];
        }
        $model = new shopAbtestVariantsModel();
        $variant_id = waRequest::cookie('waabt'.$abtest_id);
        if ($variant_id) {
            $v = $model->getById($variant_id);
            if ($v && (int) $v['abtest_id'] === $abtest_id) {
                $cache[$abtest_id] = $v['code'];
                return $cache[$abtest_id];
            }
        }
        $rows = $model->getByField('abtest_id', $abtest_id, 'id');
        if (!$rows) {
            $cache[$abtest_id] = null;
            return null;
        }
        $v = $rows[array_rand($rows)];
        wa()->getResponse()->setCookie('waabt'.$abtest_id, $v['id']);
        $cache[$abtest_id] = $v['code'];
        return $cache[$abtest_id];
    }

    /**
     * First variant code (usually A) for bots / fallback.
     */
    public static function controlCode($abtest_id)
    {
        $m = new shopAbtestVariantsModel();
        $rows = $m->getByField('abtest_id', (int) $abtest_id, true);
        if (!$rows) {
            return 'A';
        }
        usort($rows, function ($a, $b) {
            return strcmp($a['code'], $b['code']);
        });
        $code = $rows[0]['code'];
        self::ensureWaabtCookie((int) $abtest_id, $code);
        return $code;
    }

    public static function forceCookie($key, $abtest_id, $code)
    {
        wa()->getResponse()->setCookie('abdisplay_force_'.$key, $code, time() + 86400 * 30, '/');
        self::ensureWaabtCookie((int) $abtest_id, $code);
    }

    public static function clearForceCookie($key)
    {
        wa()->getResponse()->setCookie('abdisplay_force_'.$key, '', time() - 3600, '/');
    }

    /**
     * Set waabt{id} to variant id matching code.
     */
    public static function ensureWaabtCookie($abtest_id, $code)
    {
        $m = new shopAbtestVariantsModel();
        $rows = $m->getByField(array(
            'abtest_id' => (int) $abtest_id,
            'code'      => (string) $code,
        ), true);
        if (!$rows) {
            return;
        }
        $v = reset($rows);
        wa()->getResponse()->setCookie('waabt'.$abtest_id, $v['id'], time() + 86400 * 90, '/');
    }

    /**
     * @param string $key
     * @return array|null {abtest_id, variant_id, variant_code}
     */
    public static function assignment($key)
    {
        $code = self::variant($key);
        if ($code === null) {
            return null;
        }
        $experiments = self::getExperiments();
        $abtest_id = (int) $experiments[$key]['abtest_id'];
        $variant_id = (int) waRequest::cookie('waabt'.$abtest_id, 0);
        if ($variant_id <= 0) {
            $m = new shopAbtestVariantsModel();
            $rows = $m->getByField(array('abtest_id' => $abtest_id, 'code' => $code), true);
            if ($rows) {
                $variant_id = (int) reset($rows)['id'];
            }
        }
        return array(
            'experiment_key' => $key,
            'abtest_id'      => $abtest_id,
            'variant_id'     => $variant_id,
            'variant_code'   => $code,
        );
    }

    /**
     * All enabled assignments for current request (for head JS / Metrika params).
     *
     * @return array key => assignment
     */
    public static function activeAssignments()
    {
        $out = array();
        foreach (self::getExperiments() as $key => $ex) {
            if (empty($ex['enabled']) || (int) $ex['abtest_id'] <= 0) {
                continue;
            }
            $a = self::assignment($key);
            if ($a) {
                $out[$key] = $a;
            }
        }
        return $out;
    }

    /**
     * @param string $key
     * @param string $event
     * @param array $meta
     * @return int|null
     */
    public static function track($key, $event, array $meta = array())
    {
        $a = self::assignment($key);
        if (!$a) {
            // still allow track if experiment disabled? no — skip
            return null;
        }
        try {
            $model = new shopAbdisplayPluginEventModel();
            $session_id = '';
            try {
                $session_id = (string) wa()->getStorage()->getSessionId();
            } catch (Exception $e) {
                $session_id = substr(md5(waRequest::getIp().waRequest::getUserAgent()), 0, 32);
            }

            // Dedupe view: one per session+key+url
            if ($event === self::EVENT_VIEW && $session_id !== '') {
                $url = mb_substr((string) waRequest::server('REQUEST_URI', '/'), 0, 512);
                $exists = $model->query(
                    "SELECT id FROM {$model->getTableName()}
                     WHERE experiment_key = s:k AND event = s:e AND session_id = s:s AND url = s:u
                     LIMIT 1",
                    array('k' => $key, 'e' => $event, 's' => $session_id, 'u' => $url)
                )->fetchField();
                if ($exists) {
                    return (int) $exists;
                }
                $meta['url'] = $url;
            }

            $contact_id = 0;
            try {
                if (wa()->getUser()->isAuth()) {
                    $contact_id = (int) wa()->getUser()->getId();
                }
            } catch (Exception $e) {
            }

            return $model->addEvent(array(
                'experiment_key' => $a['experiment_key'],
                'abtest_id'      => $a['abtest_id'],
                'variant_id'     => $a['variant_id'],
                'variant_code'   => $a['variant_code'],
                'event'          => $event,
                'url'            => (string) waRequest::server('REQUEST_URI', '/'),
                'contact_id'     => $contact_id,
                'session_id'     => $session_id,
                'meta'           => $meta,
            ));
        } catch (Exception $e) {
            waLog::log('abdisplay track: '.$e->getMessage(), 'shop/abdisplay.log');
            return null;
        }
    }

    /**
     * Track lead against all active experiments (forms site-wide).
     */
    public static function trackLead(array $meta = array())
    {
        foreach (self::activeAssignments() as $key => $a) {
            self::track($key, self::EVENT_LEAD, $meta);
        }
    }

    /**
     * Track cart against active experiments.
     */
    public static function trackCart(array $meta = array())
    {
        foreach (self::activeAssignments() as $key => $a) {
            self::track($key, self::EVENT_CART, $meta);
        }
    }

    public static function trackOrder(array $meta = array())
    {
        foreach (self::activeAssignments() as $key => $a) {
            self::track($key, self::EVENT_ORDER, $meta);
        }
    }
}
