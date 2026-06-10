<?php


class shopCartsPluginSender {


    /**
     * @var waEmailValidator
     */
    private static $email_validator;

    /**
     * @var shopCartsPluginPhoneValidator
     */
    private static $phone_validator;

    /**
     * @param array $settings
     * @param array $cart_data
     * @param waContact $contact
     * @return array [status,comment,subject,body,body_sms]
     * @throws waException
     */
    public function sendOne($settings, $cart_data, $contact) {
        $mess = $this->_sendOne($settings, $cart_data, $contact);

        if(!empty($mess['comment']) && is_array($mess['comment'])) {
            $mess['comment'] = implode(', ', $mess['comment']);
        }
        if(!empty($mess['status']) && is_array($mess['status'])) {
            $mess['status'] = $mess['status'][0] || $mess['status'][1];
        }

        return $mess;
    }

    /**
     * @param $settings
     * @param $cart_data
     * @param $contact
     * @return array
     * @throws waException
     */
    private function _sendOne($settings, $cart_data, $contact)
    {
        switch($settings['type']) {
            case shopCartsPlugin::MESSAGE_EMAIL:
                return $this->_sendEmail($settings, $cart_data, $contact);

            case shopCartsPlugin::MESSAGE_SMS:
                return $this->_sendSMS($settings, $cart_data, $contact);

            case shopCartsPlugin::MESSAGE_EMAIL_FIRST:
                $email = $this->_sendEmail($settings, $cart_data, $contact);
                if(!$email['status']) {
                    $sms = $this->_sendSMS($settings, $cart_data, $contact);
                    return array_merge_recursive($email, $sms);
                }
                return $email;

            case shopCartsPlugin::MESSAGE_SMS_FIRST:
                $sms = $this->_sendSMS($settings, $cart_data, $contact);
                if(!$sms['status']) {
                    $email = $this->_sendEmail($settings, $cart_data, $contact);
                    return array_merge_recursive($email, $sms);
                }
                return $sms;

            case shopCartsPlugin::MESSAGE_EMAIL_SMS:
                $email = $this->_sendEmail($settings, $cart_data, $contact);
                $sms = $this->_sendSMS($settings, $cart_data, $contact);
                return array_merge_recursive($email, $sms);

            default:
                return array(
                    'status' => 0,
                    'comment' => array('Invalid message type!')
                );
        }
    }



    private function isValidEmail($email)
    {
        if(!self::$email_validator) {
            self::$email_validator = new waEmailValidator(
                array( 'required' => true ),
                array( 'required' => _wp('Email is required') )
            );
        }
        return self::$email_validator->isValid($email);
    }

    private function isValidPhone($phone)
    {
        if(!self::$phone_validator) {
            self::$phone_validator = new shopCartsPluginPhoneValidator(
                array( 'required' => true ),
                array( 'required' => _wp('Phone number is required') )
            );
        }
        return self::$phone_validator->isValid($phone);
    }

    /**
     * @param array $settings
     * @param array $data
     * @param waContact $contact
     * @return array
     * @throws waException
     */
    private function _sendEmail($settings, $data, $contact)
    {
        /**
         * @var waView $view
         */
        $view = wa()->getView();
        $view->clearAllAssign();
        $view->assign($data);
        $view->assign('customer', $contact);

        $subject = $view->fetch('string:'.$settings['subject']);
        $body = $view->fetch('string:'.$settings['body']);


        /**
         * @var shopConfig $shop_config
         */
        $shop_config =  wa('shop')->getConfig();

        if(empty($settings['sender'])) {
            $from = $shop_config->getGeneralSettings('email');
        } else {
            $from = $settings['sender'];
        }

        if(empty($settings['sender_name'])) {
            $name = $shop_config->getGeneralSettings('name');
        } else {
            $name = $settings['sender_name'];
        }


        if (!$this->isValidEmail($from))
            return array(
                'comment' => _wp('Sender\'s email is incorrect! Check the message settings.'),
                'status' => 0,
            );

        $email = $contact->get('email', 'default');
        if (!$this->isValidEmail($email))
            return array(
                'comment' => self::$email_validator->getErrors(),
                'status' => 0,
            );

        $message = new waMailMessage($subject, $body);
        $message->setTo($email);
        $message->setFrom($from, $name);

        return array(
            'email' => $email,
            'subject' => $subject,
            'body' => $body,
            'status' => $message->send(),
        );
    }


    /**
     * @param array $settings
     * @param array $data
     * @param waContact $contact
     * @return array
     */
    private function _sendSMS($settings, $data, $contact)
    {
        /**
         * @var waView $view
         */
        $view = wa()->getView();
        $view->clearAllAssign();
        $view->assign($data);
        $view->assign('customer', $contact);

        $body = $view->fetch('string:'.$settings['body_sms']);

        $phone = $contact->get('phone', 'default');
        if (!$this->isValidPhone($phone))
            return array(
                'comment' => self::$phone_validator->getErrors(),
                'status' => 0,
            );

        $message = new waSMS();

        $from = empty($settings['sender_sms']) ? null : $settings['sender_sms'];
        $status = $message->send($phone, $body, $from);

        return array(
            'phone' => $phone,
            'body_sms' => $body,
            'status' => (bool) $status,
            'comment' => !$status ? _wp('Can\'t send SMS. Gateway problem.') : ''
        );
    }
}