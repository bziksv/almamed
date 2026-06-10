<?php

class shopCartsPluginBackendMessageController extends waJsonController
{
    public function execute()
    {
        $model = new shopCartsPluginMessageModel();
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);

        if($id && waRequest::post('del')) {
            $model->deleteById($id);
            $id = 0;
        }

        /**
         * FIX tmpl undefined vars issue
         */
        $this->response = array(
            'id' => 0,
            'name' => '',
            'delay' => 1,
            'type' => 0,
            'sender' => '',
            'sender_name' => '',
            'subject' => '',
            'body' => '',
            'sender_sms' => '',
            'body_sms' => '',
            'status' => 0,
            'source' => '',
        );

        if($data = waRequest::post('shop_carts', array(), waRequest::TYPE_ARRAY)) {
            foreach ($data as $key => $val) {
                if(!$model->fieldExists($key)) unset($data[$key]);
            }
            if(empty($data['status'])) $data['status'] = 0;

            if($id) {
                $model->updateById($id, $data);
            } else {
                $id = $model->insert($data);
            }

            $data['id'] = $id;
            $this->response = array_merge($this->response, $data);

            /**
             * @var shopPlugin $plugin
             */
            $plugin = wa('shop')->getPlugin('carts');
            $settings = $plugin->getSettings();
            $settings['locale'] = waLocale::getLocale();
            $plugin->saveSettings($settings);
        } elseif($id && ($data = $model->getById($id))) {
            $this->response = array_merge($this->response, $data);
        } else {
            $this->setDefault();
        }


    }

    private function setDefault()
    {
        $this->response['delay'] = 1;
        $this->response['status'] = 1;
        $this->response['type'] = 0;
        $this->response['subject'] = _wp('We have something you forget');


        $body_template = wa()->getAppPath('plugins/carts/templates/etc/email_body_default.html');
        $body = file_get_contents($body_template);
        $texts = array(
            'title_text' => _wp('We have something you forget'),
            'restore_button_text' => _wp('Back to shopping cart'),
            'cancel_button_text' => _wp('I have already bought all I need'),
            'main_text' => _wp('Hello').'{if $customer->get("name")}, {$customer->get("name", "html")}{/if}!<br><br>
'._wp('We are sincerely glad you chose our shop.').'<br>
'._wp('If you have some troubles with your order please feel free to let us know about it!'),
            'products_text' => _wp('You added next products to your cart:')
        );

        foreach ($texts as $key => $text) {
            $body = str_replace('['.$key.']', $text, $body);
        }


        $this->response['body'] = $body;
        $this->response['body_sms'] = _wp('Any problems with order? Let us know').' {$wa->shop->settings("phone")}.
{$wa->shop->settings("name")|escape}';
    }

}
