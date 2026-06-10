<?php


class shopCartsPluginBackendTestSendController extends waJsonController {

    public function execute()
    {
        $id = waRequest::get('id', 0, waRequest::TYPE_INT);
        $code = waRequest::post('code', '');
        $email = waRequest::post('email');
        $phone = waRequest::post('phone');

        $cmm = new shopCartsPluginMessageModel();
        $csm = new shopCartsPluginStorefrontModel();
        $sender = new shopCartsPluginSender();

        $cart_products = new shopCartsPluginCartProducts();

        $cart = $cmm->getByid($id);

        $mess = array(
            'sent' => date('Y-m-d H:i:s'),
            'status' => 0,
            'comment' => ''
        );
        if(!$cart) {
            $mess['comment'] = _wp('Message not found!');
        } else {
            try {
                $storefront = $csm->getStorefrontByCode($code);

                if($cart['source'] && ($cart['source'] != $storefront)) {
                    throw new waException(_wp('Wrong storefront!'));
                }

                $customer = new shopCartsPluginCustomer();
                $customer->add('email', $email);
                $customer->add('phone', $phone);
                $customer->add('name', wa()->getUser()->get('name'));

                $data = $cart_products->getByCode($code, $storefront);

                $mess = array_merge($mess, $sender->sendOne($cart, $data, $customer));

                if(is_array($mess['comment'])) {
                    $mess['comment'] = implode(', ', $mess['comment']);
                }
                if(is_array($mess['status'])) {
                    $mess['status'] = $mess['status'][0] || $mess['status'][1];
                }
            } catch (Exception $e) {
                $mess['comment'] = $e->getMessage();
            }
        }
        $this->response = $mess;
    }
}