<?php


class shopCartsPluginReportMessagesAction extends waViewAction
{

    public function preExecute()
    {
        $u = $this->getUser();

        if (!($u->isAdmin('shop') || $u->getRights('shop', 'carts_plugin.contacts_report'))) {
            throw new waRightsException(_w("Access denied"));
        }
    }

    public function execute()
    {
        $storefront_id = waRequest::get('id', '', waRequest::TYPE_STRING_TRIM);

        $storefront_model = new shopCartsPluginStorefrontModel();
        if(!$storefront_id || !($data = $storefront_model->getById($storefront_id))) {
            throw new waException(_wp('Cart not found!'), 404);
        }

        $code = $data['code'];

        $shop_config =  wa('shop')->getConfig();
        $default_sender = $shop_config->getGeneralSettings('email');
        $this->getMessages($storefront_id);
        $this->getContact($code, $data['contact_id']);
        $this->getProducts($code, $data['storefront']);
        $this->getSources($storefront_id);
        $this->view->assign('code', $code);
        $this->view->assign('storefront_id', $storefront_id);
        $this->view->assign('sender', $default_sender);
        $this->view->assign('storefront_data', $data);
        $this->view->assign('lang', substr(wa()->getLocale(), 0, 2));
    }

    protected function getMessages($storefront_id)
    {
        $clm = new shopCartsPluginLogModel();
        $messages = $clm->getSentById($storefront_id);
        $this->view->assign('sent_messages', $messages);

        $cmm = new shopCartsPluginMessageModel();
        $messages = $cmm->select('id,name')->fetchAll();
        $this->view->assign('messages', $messages);
    }

    protected function getContact($code, $contact_id = null)
    {
        if($contact_id) {
            $customer = new shopCustomer($contact_id);
        } else {

            $model = new shopCartsPluginContactModel();
            $customer = $model->getContactByCode($code);
            if($customer) {
                $contact_id = $model->select('contact_id')->where('code = ?', $code)->fetchField();
            }
        }

        $this->view->assign('contact_id', $contact_id);
        $this->view->assign('customer', $customer);
    }

    protected function getProducts($code, $storefront)
    {
        $model = new shopCartsPluginCartProducts();
        $this->view->assign('products', $model->getByCode($code, $storefront));
    }

    protected function getSources($storefront_id)
    {
        $srm = new shopCartsPluginStorefrontRefererModel();

        $sources = $srm->select('*')->where('storefront_id = ?', $storefront_id)
            ->order('create_datetime DESC')
            ->fetchAll();

        $this->view->assign('sources', $sources);
    }
}