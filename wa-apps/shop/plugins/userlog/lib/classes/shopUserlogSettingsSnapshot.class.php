<?php

class shopUserlogSettingsSnapshot
{
    public static function captureAppSettings(array $keys)
    {
        $asm = new waAppSettingsModel();
        $result = array();
        foreach ($keys as $key) {
            $result[$key] = $asm->get('shop', $key, null);
        }
        ksort($result);
        return $result;
    }

    public static function captureGeneralSettings()
    {
        return self::captureAppSettings(array(
            'name', 'email', 'phone', 'country', 'order_format', 'order_number',
            'currency', 'use_env', 'update_stock_count_on_create_order',
            'disable_stock_count', 'enable_2x', 'mode', 'workhours',
            'use_smarty', 'theme_hash', 'round_discounts', 'round_shipping',
            'discount_method', 'discount_combine', 'affiliate',
        ));
    }

    public static function flattenForDiff(array $settings)
    {
        $flat = array();
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $flat[$key] = waUtils::jsonEncode($value);
            } else {
                $flat[$key] = (string) $value;
            }
        }
        ksort($flat);
        return $flat;
    }

    public static function diff(array $before, array $after)
    {
        $before_flat = self::flattenForDiff($before);
        $after_flat = self::flattenForDiff($after);
        $keys = array_unique(array_merge(array_keys($before_flat), array_keys($after_flat)));
        sort($keys);
        $lines = array();
        foreach ($keys as $key) {
            $b = ifset($before_flat, $key, '');
            $a = ifset($after_flat, $key, '');
            if ($b !== $a) {
                $lines[] = array(
                    'field'  => $key,
                    'label'  => self::labelForKey($key),
                    'before' => $b !== '' ? userlogHelper::plainTextForDisplay($b, 120) : '—',
                    'after'  => $a !== '' ? userlogHelper::plainTextForDisplay($a, 120) : '—',
                );
            }
        }
        return $lines;
    }

    protected static function labelForKey($key)
    {
        $labels = array(
            'stocks'                               => 'Склады',
            'rules'                                => 'Правила складов',
            'steps'                                => 'Шаги оформления',
            'ignore_stock_count'                   => 'Игнорировать остатки',
            'limit_main_stock'                     => 'Ограничить основной склад',
            'update_stock_count_on_create_order'   => 'Списание при создании заказа',
            'disable_stock_count'                  => 'Отключить учёт остатков',
            'guest_checkout'                       => 'Гостевое оформление',
            'checkout_antispam'                    => 'Антиспам checkout',
            'checkout_antispam_email'              => 'Антиспам: email',
            'checkout_antispam_captcha'            => 'Антиспам: captcha',
            'disable_backend_customer_form_validation' => 'Валидация формы покупателя',
            'name'                                 => 'Название',
            'title'                                => 'Заголовок',
            'url'                                  => 'URL',
            'status'                               => 'Статус',
            'content'                              => 'Содержимое',
            'keywords'                             => 'Keywords',
            'description'                          => 'Description',
            'service'                              => 'Услуга',
            'currencies'                           => 'Валюты',
            'primary'                              => 'Основная валюта',
            'use_product_currency'                 => 'Валюта товара',
            'round_discounts'                      => 'Округление скидок',
            'round_shipping'                       => 'Округление доставки',
            'round_services'                       => 'Округление услуг',
            'plugin_key'                           => 'Плагин',
            'settings'                             => 'Настройки плагина',
            'basic'                                => 'Seofilter: основные',
            'template_rules'                       => 'Seofilter: шаблоны',
            'filter_fields'                        => 'Seofilter: поля фильтра',
            'storefront_fields'                    => 'Seofilter: поля витрины',
            'categories'                           => 'Категории',
            'feature_rules'                        => 'Seofilter: правила характеристик',
            'seo_name'                             => 'SEO-имя фильтра',
            'seo_h1'                               => 'SEO H1',
            'meta_title'                           => 'Meta title',
            'meta_description'                     => 'Meta description',
            'meta_keywords'                        => 'Meta keywords',
            'is_enabled'                           => 'Включён',
            'feature_values_count'                 => 'Характеристик',
            'discounts_combine'                    => 'Комбинирование скидок',
            'affiliate'                            => 'Партнёрская программа',
            'states'                               => 'Статусы заказов',
            'actions'                              => 'Действия заказов',
            'notification'                         => 'Уведомление',
            'params'                               => 'Параметры уведомления',
            'cross_selling'                        => 'Cross-selling',
            'upselling'                            => 'Upselling',
            'upselling_rules'                      => 'Правила upselling',
            'type_name'                            => 'Тип товара',
            'code'                                 => 'Код',
            'type'                                 => 'Тип',
            'selectable'                           => 'Выбор значений',
            'values'                               => 'Значения',
            'icon'                                 => 'Иконка',
            'discount_description'                 => 'Описание скидки',
            'notification_name'                    => 'Имя отправителя',
            'tax'                                  => 'Налог',
            'regions'                              => 'Регионы налога',
            'zip_codes'                            => 'Индексы налога',
            'followup'                             => 'Follow-up',
            'courier'                              => 'Курьер',
            'storefronts'                          => 'Витрины',
            'template'                             => 'Шаблон',
            'plugins'                              => 'Порядок плагинов',
            'action'                               => 'Действие',
            'create_thumbnails'                    => 'Миниатюры',
            'restore_originals'                    => 'Оригиналы',
        );
        if (strpos($key, 'discount_') === 0 && $key !== 'discount_description') {
            return 'Скидка: '.substr($key, 9);
        }
        if (strpos($key, 'variant_') === 0) {
            return 'Вариант #'.substr($key, 8);
        }
        if (strpos($key, 'general.') === 0) {
            return 'SEO: '.substr($key, 8);
        }
        if (strpos($key, 'field.') === 0) {
            return 'SEO поле: '.substr($key, 6);
        }
        if (strpos($key, 'plugin.') === 0) {
            return 'SEO плагин: '.substr($key, 7);
        }
        if ($key === 'storefront_settings') {
            return 'SEO: настройки витрин';
        }
        if ($key === 'storefront_fields') {
            return 'SEO: поля витрин';
        }
        return ifset($labels, $key, $key);
    }

    public static function captureStocksSettings()
    {
        $settings = self::captureAppSettings(array(
            'ignore_stock_count',
            'limit_main_stock',
            'update_stock_count_on_create_order',
            'disable_stock_count',
        ));
        $settings['stocks'] = waUtils::jsonEncode(self::captureStocksList(), JSON_UNESCAPED_UNICODE);
        return $settings;
    }

    protected static function captureStocksList()
    {
        $list = array();
        foreach (shopHelper::getStocks() as $id => $stock) {
            $item = array(
                'id'             => is_string($id) && $id[0] === 'v' ? $id : (int) $id,
                'name'           => ifset($stock, 'name', ''),
                'public'         => (int) ifset($stock, 'public', 0),
                'low_count'      => ifset($stock, 'low_count', ''),
                'critical_count' => ifset($stock, 'critical_count', ''),
                'sort'           => (int) ifset($stock, 'sort', 0),
            );
            if (!empty($stock['substocks'])) {
                $item['substocks'] = array_values($stock['substocks']);
            }
            $list[] = $item;
        }
        return $list;
    }

    public static function captureStockRules()
    {
        $rules = (new shopStockRulesModel())->getAll('id');
        if ($rules) {
            uasort($rules, wa_lambda('$a, $b', 'return ((int) $a["sort"] > (int) $b["sort"]) - ((int) $a["sort"] < (int) $b["sort"]);'));
        }
        $list = array();
        foreach ($rules as $rule) {
            $list[] = array(
                'rule_type'       => ifset($rule, 'rule_type', ''),
                'rule_data'       => ifset($rule, 'rule_data', ''),
                'virtualstock_id' => ifset($rule, 'virtualstock_id', null),
                'stock_id'        => ifset($rule, 'stock_id', null),
            );
        }
        return array('rules' => waUtils::jsonEncode($list, JSON_UNESCAPED_UNICODE));
    }

    public static function captureCheckout()
    {
        $file = wa()->getConfig()->getConfigPath('checkout.php', true, 'shop');
        $steps = file_exists($file) ? include($file) : array();
        $settings = self::captureAppSettings(array(
            'guest_checkout',
            'checkout_antispam',
            'checkout_antispam_email',
            'checkout_antispam_captcha',
            'disable_backend_customer_form_validation',
        ));
        $settings['steps'] = waUtils::jsonEncode($steps, JSON_UNESCAPED_UNICODE);
        return $settings;
    }

    public static function captureCurrencies()
    {
        $model = new shopCurrencyModel();
        $list = array();
        foreach ($model->getCurrencies() as $code => $row) {
            $list[] = array(
                'code'          => $code,
                'rate'          => ifset($row, 'rate', ''),
                'rounding'      => ifset($row, 'rounding', ''),
                'round_up_only' => (int) ifset($row, 'round_up_only', 0),
            );
        }
        $settings = self::captureAppSettings(array(
            'use_product_currency',
            'round_discounts',
            'round_shipping',
            'round_services',
        ));
        $settings['currencies'] = waUtils::jsonEncode($list, JSON_UNESCAPED_UNICODE);
        $settings['primary'] = (string) wa('shop')->getConfig()->getCurrency();
        return $settings;
    }

    /**
     * @param array $plugin_data POST data for shipping/payment plugin
     * @param string $type shipping|payment
     */
    public static function capturePluginInstance(array $plugin_data, $type)
    {
        $id = (int) ifset($plugin_data, 'id', 0);
        $model = new shopPluginModel();
        $row = $id ? $model->getById($id) : array();
        if (!is_array($row)) {
            $row = array();
        }

        $plugin_key = (string) ifset($row, 'plugin', ifset($plugin_data, 'plugin', ''));
        $settings = array();
        if ($id && $plugin_key) {
            try {
                if ($type === 'shipping' && $plugin_key !== shopShipping::DUMMY) {
                    $settings = shopShipping::getPlugin($plugin_key, $id)->getSettings();
                } elseif ($type === 'payment' && $plugin_key !== shopPayment::DUMMY) {
                    $settings = shopPayment::getPlugin($plugin_key, $id)->getSettings();
                }
            } catch (Exception $e) {
            }
        } elseif (isset($plugin_data['settings']) && is_array($plugin_data['settings'])) {
            $settings = $plugin_data['settings'];
        }

        return array(
            'plugin_key'  => $plugin_key,
            'name'        => (string) ifset($row, 'name', ifset($plugin_data, 'name', '')),
            'status'      => (string) ifset($row, 'status', ifset($plugin_data, 'status', 0)),
            'description' => userlogHelper::plainTextForDisplay(
                (string) ifset($row, 'description', ifset($plugin_data, 'description', '')),
                120
            ),
            'settings'    => waUtils::jsonEncode($settings, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureDiscounts()
    {
        $asm = new waAppSettingsModel();
        $rows = $asm->getByField('app_id', 'shop', true);
        $result = array();
        foreach ((array) $rows as $row) {
            $name = ifset($row, 'name', '');
            if ($name === 'discounts_combine' || strpos($name, 'discount_') === 0) {
                $result[$name] = ifset($row, 'value', null);
            }
        }
        ksort($result);
        return $result;
    }

    public static function captureAffiliate()
    {
        return self::captureAffiliateSettings();
    }

    public static function captureAffiliateSettings()
    {
        $asm = new waAppSettingsModel();
        $rows = $asm->getByField('app_id', 'shop', true);
        $result = array();
        foreach ((array) $rows as $row) {
            $name = ifset($row, 'name', '');
            if (strpos($name, 'affiliate') === 0) {
                $result[$name] = ifset($row, 'value', null);
            }
        }
        ksort($result);
        return $result;
    }

    public static function captureOrderWorkflow()
    {
        $config = shopWorkflow::getConfig();
        return array(
            'states'  => waUtils::jsonEncode(ifset($config, 'states', array()), JSON_UNESCAPED_UNICODE),
            'actions' => waUtils::jsonEncode(ifset($config, 'actions', array()), JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureNotification($notification_id)
    {
        $notification_id = (int) $notification_id;
        if (!$notification_id) {
            return array();
        }
        $model = new shopNotificationModel();
        $notification = $model->getById($notification_id);
        if (!$notification) {
            return array();
        }
        $params = (new shopNotificationParamsModel())->getParams($notification_id);
        return array(
            'notification' => waUtils::jsonEncode($notification, JSON_UNESCAPED_UNICODE),
            'params'       => waUtils::jsonEncode($params, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureTypeRecommendations($type_id)
    {
        $type_id = (int) $type_id;
        if (!$type_id) {
            return array();
        }
        $type = (new shopTypeModel())->getById($type_id);
        if (!$type) {
            return array();
        }
        $upselling = (new shopTypeUpsellingModel())->getByType($type_id);
        return array(
            'type_name'       => ifset($type, 'name', ''),
            'cross_selling'   => (string) ifset($type, 'cross_selling', ''),
            'upselling'       => (string) ifset($type, 'upselling', ''),
            'upselling_rules' => waUtils::jsonEncode($upselling, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureFeature($feature_id)
    {
        $feature_id = (int) $feature_id;
        if (!$feature_id) {
            return array();
        }
        $model = new shopFeatureModel();
        $feature = $model->getById($feature_id);
        if (!$feature) {
            return array();
        }
        $values = $model->getValues($feature_id);
        return array(
            'name'       => ifset($feature, 'name', ''),
            'code'       => ifset($feature, 'code', ''),
            'type'       => ifset($feature, 'type', ''),
            'status'     => ifset($feature, 'status', ''),
            'selectable' => (string) ifset($feature, 'selectable', 0),
            'values'     => waUtils::jsonEncode($values, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureProductType($type_id)
    {
        $type_id = (int) $type_id;
        if (!$type_id) {
            return array();
        }
        $type = (new shopTypeModel())->getById($type_id);
        if (!$type) {
            return array();
        }
        return array(
            'name' => ifset($type, 'name', ''),
            'icon' => ifset($type, 'icon', ''),
            'sort' => (string) ifset($type, 'sort', 0),
        );
    }

    public static function captureShopConfigOptions()
    {
        $config = wa('shop')->getConfig();
        return array(
            'discount_description' => (string) $config->getOption('discount_description'),
            'notification_name'    => (string) $config->getOption('notification_name'),
        );
    }

    public static function captureContactFieldValues($field, $parent_field)
    {
        $model = new waContactFieldValuesModel();
        $rows = $model->select('*')->where(
            'field = s:field AND parent_field = s:parent',
            array('field' => $field, 'parent' => $parent_field)
        )->order('sort')->fetchAll();
        return array(
            'field'        => (string) $field,
            'parent_field' => (string) $parent_field,
            'values'       => waUtils::jsonEncode($rows, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureShippingPluginById($plugin_id)
    {
        $plugin_id = (int) $plugin_id;
        if (!$plugin_id) {
            return array();
        }
        $row = (new shopPluginModel())->getById($plugin_id);
        if (!$row) {
            return array();
        }
        return self::capturePluginInstance($row, 'shipping');
    }

    public static function captureTax($tax_id)
    {
        $tax_id = (int) $tax_id;
        if (!$tax_id) {
            return array();
        }
        $tax = (new shopTaxModel())->getById($tax_id);
        if (!$tax) {
            return array();
        }
        return array(
            'tax'       => waUtils::jsonEncode($tax, JSON_UNESCAPED_UNICODE),
            'regions'   => waUtils::jsonEncode((new shopTaxRegionsModel())->getByTax($tax_id), JSON_UNESCAPED_UNICODE),
            'zip_codes' => waUtils::jsonEncode((new shopTaxZipCodesModel())->getByTax($tax_id), JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureFollowup($followup_id)
    {
        $followup_id = (int) $followup_id;
        if (!$followup_id) {
            return array();
        }
        $followup = (new shopFollowupModel())->getById($followup_id);
        if (!$followup) {
            return array();
        }
        $row = $followup;
        $body = ifset($row, 'body', '');
        $row['body'] = userlogHelper::plainTextForDisplay($body, 200);
        return array(
            'followup' => waUtils::jsonEncode($row, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function captureCourier($courier_id)
    {
        $courier_id = (int) $courier_id;
        if (!$courier_id) {
            return array();
        }
        $courier = (new shopApiCourierModel())->getById($courier_id);
        if (!$courier) {
            return array();
        }
        unset($courier['api_token'], $courier['api_pin'], $courier['api_pin_expire']);
        $storefronts = (new shopApiCourierStorefrontsModel())->getByCourier($courier_id);
        return array(
            'courier'     => waUtils::jsonEncode($courier, JSON_UNESCAPED_UNICODE),
            'storefronts' => waUtils::jsonEncode($storefronts, JSON_UNESCAPED_UNICODE),
        );
    }

    public static function capturePrintformSettings($plugin_id)
    {
        $plugin_id = (string) $plugin_id;
        if ($plugin_id === '') {
            return array();
        }
        try {
            $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
            if ($plugin && method_exists($plugin, 'getSettings')) {
                return array(
                    'plugin'   => $plugin_id,
                    'settings' => waUtils::jsonEncode($plugin->getSettings(), JSON_UNESCAPED_UNICODE),
                );
            }
        } catch (Exception $e) {
        }
        return array('plugin' => $plugin_id);
    }

    public static function capturePrintformTemplate($plugin_id)
    {
        $plugin_id = (string) $plugin_id;
        if ($plugin_id === '') {
            return array();
        }
        try {
            $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
            if ($plugin && method_exists($plugin, 'getTemplate')) {
                return array(
                    'plugin'   => $plugin_id,
                    'template' => userlogHelper::plainTextForDisplay((string) $plugin->getTemplate(), 200),
                );
            }
        } catch (Exception $e) {
        }
        return array('plugin' => $plugin_id);
    }

    public static function capturePluginsOrder($type)
    {
        $plugins = (new shopPluginModel())->listPlugins($type, array('all' => true));
        $list = array();
        foreach ($plugins as $plugin) {
            $list[] = array(
                'id'   => (int) ifset($plugin, 'id', 0),
                'name' => ifset($plugin, 'name', ''),
                'sort' => (int) ifset($plugin, 'sort', 0),
            );
        }
        return array(
            'plugins' => waUtils::jsonEncode($list, JSON_UNESCAPED_UNICODE),
        );
    }
}
