<?php


class shopCartsPluginMessageSendController extends waJsonController
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
        $csm = new shopCartsPluginStorefrontModel();

        $storefront_id = waRequest::post('storefront_id', '');
        if(!$storefront_id || !($storefront_data = $csm->getById($storefront_id))) {
            $this->setError(_wp('Cart not found!'));
            return;
        }


        $clm = new shopCartsPluginLogModel();
        $sender = new shopCartsPluginSender();
        $cart_products = new shopCartsPluginCartProducts();

        $mess = array(
            'storefront_id' => $storefront_id,
            'message_id' => 0,
            'sent' => date('Y-m-d H:i:s'),
            'status' => 0,
            'comment' => ''
        );
        try {
            $storefront = $storefront_data['storefront'];
            $code = $storefront_data['code'];

            if(!$customer = $csm->getContactByCode($code)) {
                $customer = new shopCartsPluginCustomer();
            }
            if($email = waRequest::post('email')) {
                $customer->set('email', $email);
            }
            if($phone = waRequest::post('phone')) {
                $customer->set('phone', $phone);
            }

            $cart_data = $cart_products->getByCode($code, $storefront);

            $message_settings = waRequest::post();

            $mess = array_merge($mess, $sender->sendOne($message_settings, $cart_data, $customer));
            if(is_array($mess['comment'])) {
                $mess['comment'] = implode(', ', $mess['comment']);
            }
            if(is_array($mess['status'])) {
                $mess['status'] = $mess['status'][0] || $mess['status'][1];
            }
            $clm->insert($mess);


            $csm->updateById($storefront_id, array(
                'last_send_datetime' => $mess['sent']
            ));
        } catch (Exception $e) {
            $mess['comment'] = $e->getMessage();
        }
        $this->response = $mess;
    }
}