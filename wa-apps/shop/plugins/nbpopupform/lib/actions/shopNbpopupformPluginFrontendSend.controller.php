<?php

class shopNbpopupformPluginFrontendSendController extends waJsonController
{

    public function execute()
    {
        $data = waRequest::post();
		$shop_email = wa()->getConfig()->getGeneralSettings('email');

        $product = new shopProduct($data['id']);
        $data['name'] = $product['name'];
        $data['url'] = wa()->getRootUrl(true)."product/".$product['url'];

        $formPlugin = wa('shop')->getPlugin('form');
        $formSettings = $formPlugin->getSettings();

        $data['manager'] = $formSettings['email'];

        $data['roistat'] = array_key_exists('roistat_visit', $_COOKIE) ? $_COOKIE['roistat_visit'] : "неизвестно";

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $temp_path = $app_config->getAppPath('plugins/');

        $view = wa()->getView();
        $view->assign('data', $data);
        $output = $view->fetch($temp_path.'nbpopupform/templates/mail/template.html');

        $subject = "Запрос КП ".$data['name'];
        $mail_message = new waMailMessage($subject, $output);
        $mail_message->setFrom($shop_email, 'АльмаМед');
        $mail_message->setTo($data['manager'],'АльмаМед');
        $mail = $mail_message->send();

        if($mail){
            $plugin = wa('shop')->getPlugin('form');
            $settings = $plugin->getSettings();

            $From = " Almamed <$shop_email>";
            $text_client = $settings['email_client'];
            $email_client = trim(strip_tags($data['data'][2]['value']));

            $headers_client  = 'MIME-Version: 1.0' . "\r\n";
            $headers_client .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers_client .= "From: $From";

            mail($email_client, $subject, $text_client, $headers_client);
        }

        $this->response = array('response' => $data);
    }
}
