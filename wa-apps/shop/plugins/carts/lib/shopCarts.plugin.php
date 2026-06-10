<?php

class shopCartsPlugin extends shopPlugin {

    const MESSAGE_EMAIL = 0;
    const MESSAGE_SMS = 1;
    const MESSAGE_EMAIL_SMS = 2;
    const MESSAGE_EMAIL_FIRST = 3;
    const MESSAGE_SMS_FIRST = 4;
    const DEFAULT_LOCALE = 'en_US';




    public function whatsnew()
    {
        $whatsnew = $this->path.'/lib/config/whatsnew.php';
        if(file_exists($whatsnew)) {
            $whatsnew = include $whatsnew;
            end($whatsnew);
            $key = key($whatsnew);
            return $this->getSettings('whatsnew') < $key;
        }
        return false;
    }


    public function getPath()
    {
        return $this->path;
    }


    /*****************
     * Event handlers
     *****************/

    /**
     * @event backend_menu
     * @return array
     */
    public function backendMenu()
    {
        $model = new shopCartsPluginMessageModel();
        $cron_not_works = $this->getSettings('cron_alert') && ((time() - (int) $this->getSettings('last_check')) > 7200);
        $no_messages = $this->getSettings('no_messages_alert') && ($model->countAll() == 0);

        if($cron_not_works || $no_messages || $this->whatsnew())
        {
            $this->addJs('js/error_icon.js');
        }

        $view = wa()->getView();
        return array(
            'core_li' => $view->fetch($this->path.'/templates/hooks/backendReports.html')
        );
    }

    /**
     * @event contacts_links
     * @param array $params
     */
    public function contactsLinks(&$params)
    {
        foreach ($params as $contact_id => &$links) {
            $csm = new shopCartsPluginStorefrontModel();
            if ($count = $csm->countByField('contact_id', $contact_id)) {
                $links[] = array(
                    'role' => _wd('shop_carts', 'Abandoned carts'),
                    'links_number' => $count,
                );
            }
        }
    }

    /**
     * @event contacts_delete
     * @param $params
     */
    public function contactsDelete($params)
    {
        if(!empty($params)) {
            $csm = new shopCartsPluginStorefrontModel();
            $ccm = new shopCartsPluginContactModel();
            $data = array('contact_id' => null);
            $csm->updateByField('contact_id', $params, $data);
            $ccm->updateByField('contact_id', $params, $data);
        }
    }

    /**
     * @event customers_merge
     * @param $params
     */
    public function customersMerge($params)
    {
        if(!empty($params['contacts']) && !empty($params['id'])) {
            $csm = new shopCartsPluginStorefrontModel();
            $ccm = new shopCartsPluginContactModel();
            $data = array('contact_id' => $params['id']);
            $csm->updateByField('contact_id', $params['contacts'], $data);
            $ccm->updateByField('contact_id', $params['contacts'], $data);
        }
    }

    /**
     * @event frontend_head
     * @return string
     */
    public function frontendHead()
    {
        $cart = new shopCart();

        if(!$cart->getCode()) {
            return '';
        }



        /**
         * @var waSmarty3View $view
         */
        $view = wa()->getView();

        $carts_customer = array();

        if ($this->getSettings('contacts_save')) {
            /**
             * @var waSmarty3View $view
             */
            $view = wa()->getView();
            $customer = self::getContactSavedData();
            $carts_customer = array(
                'firstname' => $customer->get('firstname'),
                'lastname' => $customer->get('lastname'),
                'middlename' => $customer->get('middlename'),
                'name' => $customer->get('name'),
                'email' => $customer->get('email', 'default'),
                'phone' => $customer->get('phone', 'default'),
            );
        }

        $path = 'plugins/carts/js/carts.fontend'. (!waSystemConfig::isDebug() ? '.min' :'').'.js';
        $carts_script = wa()->getAppPath($path);
        $carts_script = file_get_contents($carts_script);

        $view->assign(array(
            'carts_customer' => $carts_customer,
            'carts_script' => $carts_script,
        ));

        return $view->fetch($this->path . '/templates/hooks/frontendHead.html');
    }

    /**
     * @event frontend_checkout,frontend_cart
     * @param null $param
     * @return string
     */
    public function frontendCheckout($param = null)
    {
        if(is_array($param) && isset($param['step']) && ($param['step'] === 'success')) {
            return '';
        }

        $this->saveCheckoutData();

        return '';
    }

    /**
     * @event frontend_product
     * @param $product
     * @return array
     */
    public function frontendProduct($product)
    {
        return array();
    }

    /**
     * @event backend_order_edit
     * @param array $order
     * @return string
     */
    public function backendOrderEdit($order)
    {
        if(!empty($order['id'])) {
            return '';
        }

        $view = wa()->getView();
        return $view->fetch($this->path . '/templates/hooks/backendOrderEdit.html');
    }


    /**
     * @event order_action.create
     * @param array $order
     */
    public function orderActionCreate($order)
    {
        $backend_order = false;
        $code = waRequest::post('carts_plugin_code');

        if(!$code) {
            $cart = new shopCart();
            $code = $cart->getCode();
        } else {
            $backend_order = true;
        }

        $storefront_model = new shopCartsPluginStorefrontModel();
        if($data = $storefront_model->getLastByCode($code)) {

            if(empty($data['last_send_datetime'])) {
                /**
                 * Удалить мусор, если ни одно сообщение не было отправлено.
                 */
                $storefront_model->deleteById($data['id']);
            } elseif($backend_order && $this->getSettings('delete_backend_order')) {
                $storefront_model->deleteById($data['id']);
            } else {
                /**
                 * Сохраним номер заказа для статистики.
                 */
                $storefront_model->updateById($data['id'], array(
                    'order_id' => $order['order_id'],
                ));
            }
        }
    }

    /**
     * @event backend_reports
     * @return array
     */
    public function  backendReports()
    {
        /**
         * @var waSmarty3View $view
         */
        $view = wa()->getView();

        return array(
            'menu_li' => $view->fetch($this->path.'/templates/hooks/backendReports.html')
        );
    }

    /**
     * @event cart_add
     * @param $item
     * @return null
     */
    public function saveStorefront($item)
    {
        $user = wa()->getUser();

        if(!$this->getSettings('admin_stat') && $user->get('is_user')) {
            return null; // don't save anything if backend user.
        }

        $domain = wa()->getRouting()->getDomain();
        $route = wa()->getRouting()->getRoute();
        $storefront = $domain.'/'.$route['url'];
        /**
         * @var shopConfig $config
         */
        $config = wa('shop')->getConfig();
        $cart = new shopCart();
        $total = $cart->total();
        $currency = $config->getCurrency(false);

        if($code = $cart->getCode()) {
            $model = new shopCartsPluginStorefrontModel();
            $contact_id = $user->getId();

            $referer = waRequest::cookie('referer');
            $landing = waRequest::cookie('landing');

            $model->saveStorefront($code, $storefront, $contact_id, $total, $currency, $referer, $landing);
        }

        return null;
    }

    /**
     * @event rights
     * @param waRightConfig $config
     */
    public function rightsConfig(waRightConfig $config)
    {
        $config->addItem('carts_plugin', _wp('Abandoned carts'), 'list', array(
            'items' => array(
                'contacts_report' => _wp('Contacts report'),
                'usage_settings' => _wp('Used resources'),
            ),
        ));
    }


    /*****************
     * Helpers
     *****************/

    /**
     * Plugin locales for CLI controller
     *
     * @return array
     */
    public static function getAvailableLocales()
    {
        return array('ru_RU', 'en_US');
    }

    /**
     * Message types
     *
     * @return array
     */
    public static function getTypeText()
    {
        return array(
            self::MESSAGE_EMAIL => array(_wp('Email only.'), 'email'),
            self::MESSAGE_SMS => array(_wp('SMS only.'), 'mobile'),
            self::MESSAGE_EMAIL_SMS => array(_wp('Both, SMS and Email.'), 'im'),
            self::MESSAGE_EMAIL_FIRST => array(_wp('Email. If fails send SMS.'), 'im'),
            self::MESSAGE_SMS_FIRST => array(_wp('SMS. If fails send Email.'), 'im'),
        );
    }

    /**
     * @return waContact
     */
    public static function getContactSavedData()
    {
        if(wa()->getUser()->isAuth()) {
            $customer = wa()->getUser();
        } else {
            $cart = new shopCart();
            $model = new shopCartsPluginContactModel();
            $customer = $model->getContactByCode($cart->getCode());
        }
        if (!$customer) {
            $customer = new shopCartsPluginCustomer();
        }
        return $customer;
    }

    /*****************
     * Mixed
     *****************/
    /**
     * @return string
     */
    protected function saveCheckoutData()
    {
        $cart = new shopCart();

        if (!$cart->getCode()) {
            return;
        }

        $contact = null;
        if (!wa()->getUser()->getId()) {
            $session_data = wa()->getStorage()->get('shop/checkout');
            if (!empty($session_data['contact'])) $contact = $session_data['contact'];
        } else {
            //$contact = new waContact(wa()->getUser()->getId());
        }

        if ($contact && ($contact instanceof waContact)) {
            $model = new shopCartsPluginContactModel();
            $model->saveContact($contact, $cart->getCode());
        }
    }
}