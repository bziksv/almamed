<?php

Class shopProductmanagerPlugin extends shopPlugin {

    protected $field_user = array(
        "name",
        "email",
        "phone",
        "photo"
    );

    /** Contact field shown in the manager block. Not unique, unlike system Email. */
    const SITE_EMAIL_FIELD = 'site_email';


    public function product_edit($data)
    {
        $manager = 0;
        if (is_object($data) && isset($data->data) && is_array($data->data) && isset($data->data['manager'])) {
            $manager = $data->data['manager'];
        } elseif (is_array($data) && isset($data['manager'])) {
            $manager = $data['manager'];
        }
        return array(
            'category_action_li' => $this->backend_user_html($manager),
        );
    }

    public function backend_products_all(){
        return array(
            'title_suffix' => '<script src="'.$this->getPluginStaticUrl().'js/script.js"></script>',
            'toolbar_organize_li' => '<li data-castom-action="assign-manager"><a href="#"><i class="icon16 user"></i>Назначить менеджера</a></li>',
        );
    }

    /**
     * @event backend_menu
     * @return array
     */
    public function backendMenu()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            return array();
        }

        $this->addJs('js/tab.js');

        $selected = waRequest::get('plugin') === 'productmanager' ? 'selected' : 'no-tab';

        return array(
            'core_li' => '<li class="' . $selected . ' productmanager-topmenu-li">'
                . '<a href="?action=plugins#/productmanager/">'
                . '<i class="icon16 user"></i> Менеджеры товаров'
                . '</a></li>',
        );
    }

    /**
     * Auto-assign manager from category binding on new/updated products.
     *
     * @param array $params
     */
    public function productPresave($params)
    {
        if (empty($params['new_data']) || !is_array($params['new_data'])) {
            return;
        }

        if (!empty($params['new_data']['manager'])) {
            return;
        }

        $category_id = (int) ifset($params['new_data'], 'category_id', 0);
        if (!$category_id) {
            return;
        }

        $manager_id = (new shopProductmanagerService())->resolveBoundManager($category_id);
        if ($manager_id) {
            $params['new_data']['manager'] = $manager_id;
        }
    }

    public static function getManagerFront($params){
        if($params){

            $field_user = array(
                "name",
                "email",
                "phone",
                "photo"
            );

            $contact = new waContact($params);
            foreach($field_user as $field){
                if($field == "phone" or $field == "email")
                    $arr_users[$field] = $contact->get($field,"value");
                else
                    $arr_users[$field] = $contact->get($field,"default");

                if($field == "photo")
                    $arr_users[$field] = $contact->getPhoto(120,160);
            }

            $plugin = wa('shop')->getPlugin('productmanager');
            $settings = $plugin->getSettings();
            $site_emails = self::contactSiteEmails($contact);
            $roistat_mail = false;
            if ($site_emails) {
                $public = array();
                foreach ($site_emails as $email) {
                    $public[] = self::roistatPublicEmail($email, $roistat_mail);
                }
                $arr_users['email'] = $public;
            } elseif (strlen($settings['default_email']) > 1) {
                $arr_users['email'] = array(self::roistatPublicEmail($settings['default_email'], $roistat_mail));
            }

            $view = wa()->getView();
            $view->assign('user', $arr_users);
            $view->assign('roistat_mail', $roistat_mail);

            return $view->fetch($_SERVER['DOCUMENT_ROOT'].'/wa-apps/shop/plugins/productmanager/templates/frontendManager.html');
        }
    }


    public function front_product($params){
        /*
        if($params['manager']){

            $view = wa()->getView();
            $view->assign('user', $this->get_user($params['manager']));

            return array(
                'block'     => $view->fetch($this->path.'/templates/frontendManager.html'),
            );
        }
        */

    }

    public function backend_user_html($id_user){

        $users = $this->get_users();

        $html = '<li>Менеджер товара</li>';
        $html .= '<select name="product[manager]">';
        $html .= '<option value="0">Не выбрано</option>';

        foreach($users as $id => $u){
            if($id_user == $id){
                $checked = "selected";
            }else{
                $checked = "";
            }

            $html .= "<option value='$id' $checked>$u[name]</option>";
        }
        $html .= '</select>';

        return $html;

    }


    public function get_users()
    {
        return (new shopProductmanagerService())->getManagers();
    }

    public function get_user($id){

        $arr_users = array();
        $contact = new waContact($id);
        foreach($this->field_user as $field){
            if($field == "phone" or $field == "email")
            $arr_users[$field] = $contact->get($field,"value");
            else
            $arr_users[$field] = $contact->get($field,"default");

            if($field == "photo")
                $arr_users[$field] = $contact->getPhoto(120,160);
        }
        $site_emails = self::contactSiteEmails($contact);
        if ($site_emails) {
            $arr_users['email'] = $site_emails;
        }
        return $arr_users;
    }

    /**
     * Emails from «Email на сайте». Empty when the field is not filled.
     *
     * @param waContact $contact
     * @return string[]
     */
    public static function contactSiteEmails(waContact $contact)
    {
        self::ensureSiteEmailField();
        return self::plainValues($contact->get(self::SITE_EMAIL_FIELD, 'value'));
    }

    /**
     * String field without the system Email uniqueness check.
     */
    public static function ensureSiteEmailField()
    {
        if (waContactFields::get(self::SITE_EMAIL_FIELD, 'person')) {
            return;
        }

        $field = waContactFields::get(self::SITE_EMAIL_FIELD, 'all');
        if (!$field) {
            $field = new waContactStringField(self::SITE_EMAIL_FIELD, array(
                'ru_RU' => 'Email на сайте',
                'en_US' => 'Email on site',
            ), array(
                'unique' => false,
                'multi' => true,
            ));
            waContactFields::createField($field);
            $field = waContactFields::get(self::SITE_EMAIL_FIELD, 'all');
        }
        if ($field) {
            waContactFields::enableField($field, 'person');
        }
    }

    /**
     * info@almamed.su и любой другой ящик @almamed.su показываем как номер визита Roistat.
     * Без куки остаётся info@almamed.su — как на странице контактов до подстановки.
     *
     * @param string $email
     * @param bool $tracking
     * @return string
     */
    public static function roistatPublicEmail($email, &$tracking = false)
    {
        $email = trim((string) $email);
        if (preg_match('/@almamed\.su$/i', $email)) {
            $tracking = true;
            return shopCustom::roistat_cookie().'@almamed.su';
        }
        return $email;
    }

    /**
     * @param mixed $raw
     * @return string[]
     */
    protected static function plainValues($raw)
    {
        if ($raw === null || $raw === '') {
            return array();
        }
        if (!is_array($raw)) {
            $raw = array($raw);
        }
        $out = array();
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = ifset($item, 'value', '');
            }
            $item = trim((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }


}
