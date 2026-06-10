<?php


class shopCartsPluginFrontendSaveController extends waJsonController {

    public function execute()
    {
        /**
         * @var shopCartsPlugin $plugin
         */
        $customer = waRequest::post('customer', array(), waRequest::TYPE_ARRAY);

        if($customer) {
            $plugin = wa()->getPlugin('carts');
            $contact = new waContact();
            $auth_contact = wa()->getUser();
            $auth_save = $plugin->getSettings('auth_save') && $auth_contact->isAuth();

            foreach ($customer as $field => $value) {
                if($field == 'id') {
                    continue;
                }

                $value = shopCartsPluginCustomer::prepareValue($value);

                if(!empty($value)) {
                    $contact->set($field, $value, false);

                    if($auth_save) {
                        $auth_contact->set($field, $value, false);
                    }
                }
            }

            $cart = new shopCart();
            $model = new shopCartsPluginContactModel();
            $model->saveContact($contact, $cart->getCode());

            if($auth_save) {
                $auth_contact->save();
            }
        }
    }
}