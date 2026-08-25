<?php

class shopLeadsPlugin extends shopPlugin
{
    const SOURCE_KP = 'kp';
    const SOURCE_ZAYAVKA = 'zayavka';
    const SOURCE_404 = '404';
    const SOURCE_WAIT = 'wait';

    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DONE = 'done';
    const STATUS_SPAM = 'spam';

    /**
     * @event backend_menu
     * @return array
     */
    public function backendMenu()
    {
        $new_count = 0;
        $show_badge = true;
        try {
            $plugin = wa('shop')->getPlugin('leads');
            $show_badge = (bool) $plugin->getSettings('show_badge');
            if ($show_badge) {
                $model = new shopLeadsPluginLeadModel();
                $new_count = $model->countNew();
            }
        } catch (Exception $e) {
            // ignore
        }

        $view = wa()->getView();
        $view->assign(array(
            'plugin_url' => $this->getPluginStaticUrl(),
            'new_count'  => $show_badge ? $new_count : 0,
        ));
        return array(
            'core_li' => $view->fetch($this->path . '/templates/hooks/backendMenu.html'),
        );
    }

    /**
     * Safe lead insert — never breaks form send flow.
     *
     * @param array $data
     * @return int|null lead id
     */
    public static function logLead(array $data)
    {
        try {
            $plugin = wa('shop')->getPlugin('leads');
        } catch (Exception $e) {
            return null;
        }
        if (!$plugin) {
            return null;
        }

        $source = (string) ifset($data, 'source', '');
        if (!self::isSourceEnabled($plugin, $source)) {
            return null;
        }

        if (!(bool) $plugin->getSettings('store_payload')) {
            unset($data['payload']);
        }

        try {
            $model = new shopLeadsPluginLeadModel();
            $dup_min = (int) $plugin->getSettings('duplicate_minutes');
            return $model->addLead($data, $dup_min);
        } catch (Exception $e) {
            waLog::log('leads plugin logLead: ' . $e->getMessage(), 'shop/leads.log');
            return null;
        }
    }

    /**
     * @param shopLeadsPlugin $plugin
     * @param string $source
     * @return bool
     */
    public static function isSourceEnabled($plugin, $source)
    {
        $map = array(
            self::SOURCE_KP      => 'log_kp',
            self::SOURCE_ZAYAVKA => 'log_zayavka',
            self::SOURCE_404     => 'log_404',
            self::SOURCE_WAIT    => 'log_wait',
        );
        if (!isset($map[$source])) {
            return true;
        }
        return (bool) $plugin->getSettings($map[$source]);
    }

    public static function sourceLabels()
    {
        return array(
            self::SOURCE_KP      => 'Запросить КП',
            self::SOURCE_ZAYAVKA => 'Оставить заявку',
            self::SOURCE_404     => 'Форма 404',
            self::SOURCE_WAIT    => 'Окно при уходе',
        );
    }

    public static function statusLabels()
    {
        return array(
            self::STATUS_NEW         => 'Новая',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_DONE        => 'Закрыта',
            self::STATUS_SPAM        => 'Спам / дубль',
        );
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        try {
            $plugins = wa('shop')->getConfig()->getPlugins();
            return !empty($plugins['leads']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param array $data
     * @return int|null
     */
    public static function logLeadSafe(array $data)
    {
        if (!self::isEnabled()) {
            return null;
        }
        try {
            wa('shop')->getPlugin('leads');
        } catch (Exception $e) {
            return null;
        }
        return self::logLead($data);
    }
}
