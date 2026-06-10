<?php

class shopWaitPluginFrontendWaitsendemailController extends waJsonController
{

    /*
     * send email
     */
    public function execute()
    {
        $ajax = waRequest::isXMLHttpRequest();

        if ($ajax) {
        	//$httpReferer = waRequest::server('HTTP_REFERER');  //HTTP_REFERER - может быть пустым
            $name = trim(waRequest::post('name'));
			$email = trim(waRequest::post('email'));
			$phone = trim(waRequest::post('phone'));
			$urlReferer = trim(waRequest::post('urlReferer', ''));
		
            $shopName = wa('shop')->getConfig()->getGeneralSettings('name');
            $shopEmail = wa('shop')->getConfig()->getGeneralSettings('email');
		
       		$model = new waAppSettingsModel();
        	//$WAemail = $model->get('webasyst', 'email');
        	$WAsender = $model->get('webasyst', 'sender');

			$plugin = wa('shop')->getPlugin('wait');
			$result_text = trim($plugin->getSettings('result_text'));
			$emailTo = ($plugin->getSettings('email')) ? trim($plugin->getSettings('email')) : trim($shopEmail);
			$emailFrom = ($plugin->getSettings('email_from')) ? trim($plugin->getSettings('email_from')) : trim($WAsender);
            $email_subject = $plugin->getSettings('email_subject'); 
			
			if ($name OR $email OR $phone) {

				$body = "<p>Заполнено всплывающее окно при уходе с сайта</p>";
				if ($urlReferer) {
					$body .= "<p>На странице {$urlReferer}</p>";
				}
				if ($name) {
					$body .= "<p>Имя: <strong>{$name}</strong></p>";
				}
				if ($email) {
					$body .= "<p>E-mail: <strong>{$email}</strong></p>";
				} 
				if ($phone) {
					$body .= "<p>Телефон: <strong>{$phone}</strong></p>";
				}
                $body .= "<br/>---<br/>" . $shopName;

                $to = array();
                if (strpos($emailTo, ',') !== false) {
                	$emailsArr = explode(',', $emailTo);

                	foreach ($emailsArr as $mailTo) {
                		if (trim($mailTo)) {
                			$to[$mailTo] = $shopName;
                		}
                	}
                } else {
                	$to = array($emailTo => $shopName);
                }

				$from = array($emailFrom => $shopName);
                $mail = new waMailMessage($email_subject, $body);
                $mail->setTo($to);
                $mail->setFrom($from);
                $send = $mail->send();

                $this->response = array(
                    'status' => $send,
                    'result_text' => $result_text
                );

			}
		}
    }

}
