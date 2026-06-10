<?php

class shopWaitPluginFrontendWaitcouponController extends waJsonController
{
	
    /*
     * return result create coupon
     * @return array
     */
    public function execute()
    {
        $ajax = waRequest::isXMLHttpRequest();

        if ($ajax) {
			$code = htmlspecialchars(waRequest::post('code'), ENT_QUOTES);
			
            //установка куки, по умолчанию 30 дней
            $plugin = wa('shop')->getPlugin('wait');
            $result_text = trim($plugin->getSettings('result_text'));
            $hours = intval($plugin->getSettings('t1_hours'));

            if ($hours > 0) {
                $expire = time() + ($hours * 3600);
                wa()->getResponse()->setCookie('wait_code', $code, time() + 3600 * $hours, '/');
                wa()->getResponse()->setCookie('wait_code_expire', $expire, time() + 3600 * $hours, '/');
            }
			
            $this->response = array(
                'result' => array(
					'is' => 'success',
                    'result_text' => $result_text
				)
            );
        }
    }
    
}