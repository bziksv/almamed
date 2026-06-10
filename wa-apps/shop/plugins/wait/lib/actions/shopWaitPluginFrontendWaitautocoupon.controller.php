<?php

class shopWaitPluginFrontendWaitautocouponController extends waJsonController
{
	public $table = 'shop_coupon';
	
    /*
     * return result create coupon
     * @return array
     */
    public function execute()
    {
        $ajax = waRequest::isXMLHttpRequest();

        if ($ajax) {
            $type = intval(waRequest::post('type'));

            $resultAdd = array();
            $plugin = wa('shop')->getPlugin('wait');
            $result_text = trim($plugin->getSettings('result_text'));
            $flexdiscount_id = intval($plugin->getSettings('flexdiscount_id'));

            if (!$type) {

                /*
                 * Штатные купоны Shop Script (автогенерация купона)
                 */

                //автоудаление купонов
                $couponm = new shopCouponModel();
                $resultDel = $couponm->query("DELETE FROM " . $this->table . " WHERE `used` = 0 AND `expire_datetime` < NOW()");
                
                //добавление купона
                //{"status":"ok","data":{"result":{"is":"success","code":"WAIT0B40U2N7L2","type":"%","value":5.5,"hours":24}}}
                $resultAdd = $this->create_coupon();
                
                $hours = intval($plugin->getSettings('t0_hours'));

            } elseif ($type == 6) {

                /* 
                 * Плагин Гибкие скидки и бонусы (автогенерация купона)
                 */

                if (class_exists('shopFlexdiscountPluginHelper') && $flexdiscount_id > 0) {
                    try {
                        $coupon_code_flexdiscount = shopFlexdiscountPluginHelper::generateCoupon($flexdiscount_id);
                        $resultAdd = array(
                            'is' => 'success',
                            'code' => $coupon_code_flexdiscount,                           
                        );
                    } catch (Exception $e) {
                        //
                    }
                }

                $hours = intval($plugin->getSettings('t6_hours'));

            }

			//установка куки, в настройках, по умолчанию 30 дней
            if ($hours > 0 && isset($resultAdd['code']) && $resultAdd['code']) {
                $expire = time() + ($hours * 3600);
                wa()->getResponse()->setCookie('wait_code', $resultAdd['code'], time() + 3600 * $hours, '/');
                wa()->getResponse()->setCookie('wait_code_expire', $expire, time() + 3600 * $hours, '/');
            }
            
            $this->response = array(
                'result' => $resultAdd,
                'result_text' => $result_text
            );
        }
    }

    /*
     * return coupon code
     * @return string
     */
    private function generate_coupon_code()
    {
        $alphabet = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
        $result = '';
        while (strlen($result) < 10) {
            $result .= $alphabet{mt_rand(0, strlen($alphabet)-1)};
        }

        return 'WAIT' . $result;
    }

    /*
     * add coupon in shop
     * @return array
     */
    private function create_coupon()
    {
        $plugin = wa('shop')->getPlugin('wait');

		$t0_hours = intval($plugin->getSettings('t0_hours'));
        $t0_type = intval($plugin->getSettings('t0_type'));
		$t0_type = str_replace(array(0, 1, 2), array('%', 'RUB', '$FS'), $t0_type);
        $t0_value = $plugin->getSettings('t0_value');
        $t0_value = floatval(str_replace(',', '.', $t0_value));
        $t0_comment = htmlspecialchars($plugin->getSettings('t0_comment'), ENT_QUOTES);

        if ($t0_value > 0 && $t0_hours > 0) {
		
            $code = $this->generate_coupon_code();
            $time = $t0_hours * 3600 + time();
            $expire_datetime = date('Y-m-d H:i:s', $time);
            $create_datetime = date('Y-m-d H:i:s', time());
            $create_contact_id = wa()->getUser()->getId();

            $coupon_data = array(
                'code' => $code,
                'type' => $t0_type,
                'limit' => 1,
                'value' => $t0_value,
                'comment' => $t0_comment,
                'expire_datetime' => $expire_datetime,
                'create_datetime' => $create_datetime,
                'create_contact_id' => 0,
            );

            $couponm = new shopCouponModel();
            $new_coupon_id = $couponm->insert($coupon_data);

            if ($new_coupon_id > 0) {
                $return = array(
                    'is' => 'success',
                    'code' => $code,
                    'type' => $t0_type,
                    'value' => $t0_value,
                    'hours' =>  $t0_hours,
                );
                return $return;
            } else {
                return array(
                    'is' => 'error',
                    'text' => 'При добавлении купона возникла ошибка, свяжитесь с администратором магазина.',
                );
            }            

        } else {
            return array(
				'is' => 'error',
				'text' => 'При добавлении купона возникла ошибка, свяжитесь с администратором магазина.',
			);
        }
    }    
    
}
