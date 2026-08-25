<?php

class shopNbpopupformPluginFrontendSendController extends waJsonController
{
    const FORM_ID = 8;

    public function execute()
    {
        $product_id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if (!$product_id) {
            $this->setError('Не указан товар');
            return;
        }

        $product = new shopProduct($product_id);
        if (!$product->getId()) {
            $this->setError('Товар не найден');
            return;
        }

        $values = $this->normalizeSubmittedFields(waRequest::post('data', array(), waRequest::TYPE_ARRAY));

        $fields_model = new shopNbpopupformFormsinputModel();
        $form_fields = $fields_model->getByField('id_form', self::FORM_ID, true);
        if (!$form_fields) {
            $this->setError('Форма не настроена');
            return;
        }

        if (!$this->validateFields($form_fields, $values)) {
            return;
        }

        $shop_email = wa()->getConfig()->getGeneralSettings('email');
        $form_plugin = wa('shop')->getPlugin('form');
        $form_settings = $form_plugin->getSettings();
        $manager_email = ifset($form_settings, 'email', '');

        if (!$manager_email) {
            $this->setError('Не настроен email получателя');
            return;
        }

        $mail_fields = array();
        foreach ($form_fields as $field) {
            if ($field['type'] === 'checkbox') {
                continue;
            }
            $name = $field['names'];
            $mail_fields[] = array(
                'name' => $name,
                'value' => ifset($values, $name, ''),
            );
        }

        $data = array(
            'name' => $product['name'],
            'url' => wa()->getRootUrl(true) . 'product/' . $product['url'],
            'data' => $mail_fields,
            'roistat' => waRequest::cookie('roistat_visit', 'неизвестно'),
        );

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $temp_path = $app_config->getAppPath('plugins/');

        $view = wa()->getView();
        $view->assign('data', $data);
        $output = $view->fetch($temp_path . 'nbpopupform/templates/mail/template.html');

        $subject = 'Запрос КП ' . $data['name'];
        $mail_message = new waMailMessage($subject, $output);
        $mail_message->setFrom($shop_email, 'АльмаМед');
        $mail_message->setTo($manager_email, 'АльмаМед');

        if (!$mail_message->send()) {
            $this->setError('Не удалось отправить заявку');
            return;
        }

        $email_client = ifset($values, 'E-mail', '');
        $text_client = ifset($form_settings, 'email_client', '');
        if ($email_client && $text_client) {
            $headers_client = "MIME-Version: 1.0\r\n";
            $headers_client .= "Content-type: text/html; charset=utf-8\r\n";
            $headers_client .= 'From: Almamed <' . $shop_email . ">\r\n";
            mail($email_client, $subject, $text_client, $headers_client);
        }

        $this->response = array('response' => true);
    }

    private function normalizeSubmittedFields($raw_fields)
    {
        $values = array();
        if (!is_array($raw_fields)) {
            return $values;
        }

        foreach ($raw_fields as $item) {
            if (!is_array($item) || !isset($item['name'])) {
                continue;
            }
            $name = trim($item['name']);
            if ($name === '') {
                continue;
            }
            $values[$name] = trim(strip_tags(ifset($item, 'value', '')));
        }

        return $values;
    }

    private function validateFields($form_fields, $values)
    {
        foreach ($form_fields as $field) {
            $name = $field['names'];
            $type = $field['type'];
            $required = !empty($field['required']);
            $value = ifset($values, $name, '');

            if ($type === 'checkbox') {
                if ($required && !$value) {
                    $this->setError('Подтвердите согласие на обработку персональных данных');
                    return false;
                }
                continue;
            }

            if ($required && $value === '') {
                $this->setError('Заполните поле «' . $name . '»');
                return false;
            }

            if ($name === 'E-mail') {
                if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->setError('Укажите корректный E-mail');
                    return false;
                }
            }

            if ($name === 'Телефон' && $required) {
                $digits = preg_replace('/\D/', '', $value);
                if (strlen($digits) < 10) {
                    $this->setError('Укажите корректный телефон');
                    return false;
                }
            }
        }

        return true;
    }
}
