<?php

class shopWaitPlugin extends shopPlugin
{

	private $carts_url = false;
	private $subscribe_url = false;
	private $mailer_form_id = 0;

	/* 
	 * integrationPlugins function
	 * @set carts_url, subscribe_url
	 */
	private function integrationPlugins()
	{
		/* Если установлен плагин "Брошенные корзины", то отправляем запрос на него */
		$plugins = wa()->getConfig()->getAppConfig('shop')->getPlugins();
		if (isset($plugins['carts'])) {
			$this->carts_url = wa()->getRouteUrl('shop/frontend/save', array('plugin' => 'carts'));
		}

		/* Для отправки почты на подписку в приложение "Рассылки" */
		$plugin = wa('shop')->getPlugin('wait');
		$mailer_form_id = intval($plugin->getSettings('t5_form_id'));
		$subscribe_url = wa()->getRouteUrl('mailer/frontend/subscribe');
		if ($mailer_form_id > 0 AND $subscribe_url) {
			$this->mailer_form_id = $mailer_form_id;
			$this->subscribe_url = $subscribe_url;
		}
	}

	/*
	 * frontend_checkout
	 */
	public function frontendCheckout($params)
	{
		if (waRequest::cookie('wait_code', '', 'string')) {
			// удаляем куки на этапе checkout
			wa()->getResponse()->setCookie('wait_code', '', time() - 3600, '/');
		} 

		return '';
	}

	/*
	 * order_action.create
	 */
	public function orderActionCreate($params)
	{
		$plugin = wa('shop')->getPlugin('wait');
		$after_order = intval($plugin->getSettings('after_order'));
		//записываем в куки номер заказа на количество часов
		wa()->getResponse()->setCookie('wait_order_id', $params['order_id'], time() + 3600 * $after_order, '/');
	}

	/*
	 * frontend_footer
	 */
	public function frontendFooter()
	{
		$root = wa()->getRootUrl();
		$front = wa()->getRouteUrl('shop/frontend');
		$switchoff = intval($this->getSettings('switchoff'));
		$templateCss = $this->getSettings('templateCss');

		/* 
		 *	Если купон уже выдан, то показываем только верхний блок 
		 */
		$code = waRequest::cookie('wait_code', '', 'string');
		if ($code) {
			$wait_code_expire = waRequest::cookie('wait_code_expire', '', 'string');
			//$expire = date('d.m.Y H:i:s', $wait_code_expire);
			$diffsec = $wait_code_expire - time();
			if ($diffsec > 3600) {
				$diff = ceil($diffsec / 3600);
				$diff = "(осталось {$diff} ч.)";
			} elseif ($diffsec > 0) {
				$diff = ceil($diffsec / 60);
				$diff = "(осталось {$diff} м.)";
			} else {
				$diff = '';
			}
			
			$head = "<link rel='stylesheet' href='{$root}wa-apps/shop/plugins/wait/css/style1.css'>";
			if ($templateCss && md5($templateCss) != 'b9250a818bcad8ce772f2139d3e7a58b') $head .= "<style>".$templateCss."</style>";

			$head .= "<script>var waitGlobalFrontend = \"{$front}\"</script>";
			$head .= "<script src='{$root}wa-apps/shop/plugins/wait/js/wait1.js'></script>";
			$html = "<div class='wait-plugin-top'><div>Не забудьте получить скидку, примените код купона при оформлении заказа: <span class='coupon-code'>{$code}</span> <span>{$diff}</span> <span class='close'></span></div></div>";
			return $head . $html;
		}

		/* 
		 * Только для неавторизованных пользователей. Перенести в настройки? 
		 */ 
		if ($switchoff OR waRequest::isMobile() OR wa()->getUser()->getId() > 0 OR waRequest::cookie('wait_show', '', 'string')) {
			return '';
		}

		$img = intval($this->getSettings('img'));				//Картинка
		$type = intval($this->getSettings('type'));				//Тип показа (0,1,5,4) удалили 2,3
		$title = $this->getSettings('title');					//Заголовок
		$text = $this->getSettings('text');						//описание
		$pdn = intval($this->getSettings('pdn'));				//ПДН вкл
		$pdn_text = $this->getSettings('pdn_text');				//ПДН текст
		$templateJs = $this->getSettings('templateJs');			//Дополнительные скрипты
		$templateCss = $this->getSettings('templateCss');		//Дополнительные стили

		$head = "<link rel='stylesheet' href='{$root}wa-apps/shop/plugins/wait/css/style.css'>\r\n";
		if ($templateCss && md5($templateCss) != 'b9250a818bcad8ce772f2139d3e7a58b') $head .= "<style>".$templateCss."</style>\r\n";

		$head .= "<script>var waitGlobalFrontend = \"{$front}\"</script>\r\n";
		$head .= "<script>
			function addScript(src){
				var script = document.createElement('script');
				script.src = src;
				script.async = false;
				document.head.appendChild(script);
			}
			if (typeof(jQuery) == 'undefined') addScript('{$root}wa-apps/shop/plugins/wait/js/jquery-3.1.0.min.js');
			if (typeof(inputmask) == 'undefined') addScript('{$root}wa-apps/shop/plugins/wait/js/jquery.inputmask.bundle.min.js');
			addScript('{$root}wa-apps/shop/plugins/wait/js/wait.js');
		</script>\r\n";

		if ($templateJs) $head .= "<script>" . $templateJs . "</script>\r\n";

		/*
		$head .= "<script>if (typeof(jQuery) == 'undefined') {document.write('<script src=\"{$root}wa-apps/shop/plugins/wait/js/jquery-3.1.0.min.js\"><\/script>');} </script>\r\n";
		$head .= "<script src='{$root}wa-apps/shop/plugins/wait/js/jquery.inputmask.bundle.min.js'></script>";
		$head .= "<script>var waitGlobalFrontend = \"{$front}\"</script>\r\n";
		if ($templateJs) $head .= "<script>" . $templateJs . "</script>";
		$head .= "<script src='{$root}wa-apps/shop/plugins/wait/js/wait.js'></script>\r\n";
		*/

		$head .= "
		<script>
		var alert_flag = true; 
		$(function(){ 
			$(document).mouseleave(function(e){
				if (e.clientY < 0) {
					if (alert_flag) {
						modalShowXV();
					}
				}
			});
		});
		</script>\r\n";

		/* 
		 * Интеграция с плагином и приложением 
		 */
		$this->integrationPlugins();

		$view = wa()->getView();
		$view->assign('img', $img);
		$view->assign('title', $title);
		$view->assign('text', $text);

		$popup = '';
		$extraClass = '';

		if ($type == 0) {

			/* 
			 * Штатные купоны Shop Script (автогенерация купона)
			 */
			$pdn = 0;
			$extraClass = 'wait-autocoupon';

			$t0_hours = intval($this->getSettings('t0_hours'));
			$t0_type = intval($this->getSettings('t0_type'));
			$t0_type = str_replace(array(0, 1, 2), array('%', ' рублей', '$FS'), $t0_type);
			$t0_value = $this->getSettings('t0_value');
			$t0_value = floatval(str_replace(',', '.', $t0_value));

			if ($t0_value && $t0_hours > 0) {
				if ($t0_type == '$FS') {
					$popup .= '<div class="type">Бесплатная доставка</div>';
				} else {
					$popup .= "<div class='type'>Скидка {$t0_value}{$t0_type} </div>";
				}
				
				$popup .= "<div class='hours'>Действует {$t0_hours} ч.</div><br>";
				$popup .= "
					<input type='button' class='autocoupon' value='Получить купон' onclick='autocouponClick({$type})'>
					<input type='text' class='autocoupon-value' value=''>
				";
			} else {
				return '';
			}

		} elseif ($type == 6) {

			/* 
			 * Плагин Гибкие скидки и бонусы (автогенерация купона)
			 */
			$pdn = 0;
			$extraClass = 'wait-autocoupon';

			$t6_value = $this->getSettings('t6_value');
			$t6_hours = intval($this->getSettings('t6_hours'));
			$flexdiscount_id = intval($this->getSettings('flexdiscount_id'));

			if ($t6_value && $flexdiscount_id > 0) {
				$popup .= "<div class='type'>Скидка {$t6_value} </div>";
				if ($t6_hours > 0) {
					$popup .= "<div class='hours'>Действует {$t6_hours} ч.</div><br>";
				}
				$popup .= "
					<input type='button' class='autocoupon' value='Получить купон' onclick='autocouponClick({$type})'>
					<input type='text' class='autocoupon-value' value=''>
				";
			} else {
				return '';
			}

		} elseif ($type == 1) {

			/* 
			 * Свой код купона 
			 */
			$pdn = 0;
			$extraClass = 'wait-autocoupon';
			
			$t1_hours = intval($this->getSettings('t1_hours'));
			$t1_code = $this->getSettings('t1_code');
			$t1_value = $this->getSettings('t1_value');
			
			if ($t1_code && $t1_value) {
				$popup .= "<div class='type'>Скидка {$t1_value} </div>";
				if ($t1_hours) { 
					$popup .= "<div class='hours'>Действует {$t1_hours} ч.</div><br>";
				}
				$popup .= "<input type='text' class='autocoupon-value coupon-value' value='{$t1_code}'>";
			} else {
				return '';
			}

		} elseif ($type == 5) {

			/* 
			 * Поля для ввода имени/e-mail/телефона 
			 */
			$t5_field_name = intval($this->getSettings('t5_field_name'));
			$t5_field_email = intval($this->getSettings('t5_field_email'));
			$t5_field_phone = intval($this->getSettings('t5_field_phone'));
			$t5_name = $this->getSettings('t5_name');
			
			if ($t5_field_name > 0) {
				$class = ($t5_field_name == 2) ? 'wait-required' : '';
				$popup .= "<input placeholder='Имя' type='text' class='wait-name-value {$class}' value=''>";
			}
			if ($t5_field_email > 0) {
				$class = ($t5_field_email == 2) ? 'wait-required' : '';
				$popup .= "<input placeholder='E-mail' type='text' class='wait-email-value {$class}' value=''>";
			}
			if ($t5_field_phone > 0) {
				$class = ($t5_field_phone == 2) ? 'wait-required' : '';
				$popup .= "<input placeholder='Телефон' type='text' class='wait-phone-value {$class}' value=''>";
			}
			$popup .= "<input type='button' class='adaptive-button' value='{$t5_name}' onclick='sendEmailClick()'>";
			$popup .= "<input type='hidden' name='carts_url' value='{$this->carts_url}'>";
			$popup .= "<input type='hidden' name='subscribe_url' value='{$this->subscribe_url}'>";
			$popup .= "<input type='hidden' name='mailer_form_id' value='{$this->mailer_form_id}'>";

			if ($t5_field_name > 0 AND $t5_field_email > 0 AND $t5_field_phone > 0) {
				$extraClass = 'count3';
			}
			
		} elseif ($type == 4) {

			/* 
			 * Ссылка на отдельную страницу 
			 */
			$pdn = 0;
			
			$t4_name = ($this->getSettings('t4_name')) ? $this->getSettings('t4_name') : 'Открыть';
			$t4_url = $this->getSettings('t4_url');

			if ($t4_url) {
				$popup .= "<form action='{$t4_url}'><input type='submit' class='gift-url' data-url='' value='{$t4_name}'></form>";
			} else {
				return '';
			}

		}

		$view->assign('pdn', $pdn);
		$view->assign('pdn_text', $pdn_text);
		$view->assign('extraClass', $extraClass);
		$view->assign('popup', $popup);
		$path = wa()->getAppPath('plugins/wait/templates/popup.html', 'shop');
		$html = $view->fetch($path);		

		return $head . $html;
	}
	
}
