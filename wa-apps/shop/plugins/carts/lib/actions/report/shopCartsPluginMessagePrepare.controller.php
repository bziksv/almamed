<?php


class shopCartsPluginMessagePrepareController extends waJsonController
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
        $code = waRequest::post('code', '', waRequest::TYPE_STRING_TRIM);
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);

        $cmm = new shopCartsPluginMessageModel();
        $csm = new shopCartsPluginStorefrontModel();
        $cart_products = new shopCartsPluginCartProducts();


        $contact = $csm->getContactByCode($code);
        if(!$contact) $contact = new shopCartsPluginCustomer();
        $storefront = $csm->getStorefrontByCode($code);
        $data = $cart_products->getByCode($code, $storefront);
        $settings = $cmm->getById($id);



        /**
         * @var waView $view
         */
        $view = wa()->getView();
        $view->clearAllAssign();
        $view->assign($data);
        $view->assign('customer', $contact);

        $this->response = array(
            'body_sms' => $view->fetch('string:'.$settings['body_sms']),
            'subject' => $view->fetch('string:'.$settings['subject']),
            'body' => $view->fetch('string:'.$settings['body']),
            'sender' => $settings['sender'],
            'sender_sms' => $settings['sender_sms'],
        );
    }
}