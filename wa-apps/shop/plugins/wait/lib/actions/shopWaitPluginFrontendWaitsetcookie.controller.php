<?php

class shopWaitPluginFrontendWaitsetcookieController extends waJsonController
{

    /*
     * set cookie
     */
    public function execute()
    {
        $ajax = waRequest::isXMLHttpRequest();

        if ($ajax) {
            /*
             * установка куки, в настройках, по умолчанию 30 дней
             * проверка наличия товаров в корзине
             * проверка ссылки для показа
             */
            $plugin = wa('shop')->getPlugin('wait');
            $t0_cookie = intval($plugin->getSettings('t0_cookie'));
			$summInCarts = floatval(str_replace(',', '.', $plugin->getSettings('summ_in_carts')));
			$show = 1;

			//$REQUEST_URI = waRequest::server('REQUEST_URI');
			$urlReferer = trim(waRequest::post('urlReferer', ''));
			//$urlArr = parse_url(waRequest::server('HTTP_REFERER')); //HTTP_REFERER - может быть пустым
			$urlArr = parse_url($urlReferer);
			$dont_show_cart = intval($plugin->getSettings('dont_show_cart'));
			$dont_show_checkout = intval($plugin->getSettings('dont_show_checkout'));
			//$dont_show_yandexmarket = intval($plugin->getSettings('dont_show_yandexmarket'));
			$dont_show_urls = $plugin->getSettings('dont_show_urls');

			$dontShowArr = array();
			$dontShowMaskUrl = array();
			$dontShowUrls = explode(PHP_EOL, $dont_show_urls);

			foreach ($dontShowUrls as $key => $url) {
				$url = trim($url);
				if (!$url) continue;

				if (strpos($url, '*') !== false) {
					$dontShowMaskUrl[] = $url;
				} else {
					$dontShowArr[] = $url;
				}
			}

			foreach ($dontShowMaskUrl as $maskUrl) {
				$maskUrl = str_replace('*', '(.)*', $maskUrl);

				if (preg_match("/{$maskUrl}/", $urlReferer)) {
					$show = 0;

					break;
				}
			}

			if ($show) {
				if ($urlArr['path'] == '/checkout/success/') {
					$show = 0;
				} elseif ($dont_show_cart && $urlArr['path'] == '/cart/') {
					$show = 0;
				} elseif ($dont_show_checkout && mb_substr($urlArr['path'], 0, 10) == '/checkout/') {
					$show = 0;
				} elseif (in_array($urlArr['path'], $dontShowArr)) {
					$show = 0;
				} /*elseif ($dont_show_yandexmarket && isset($urlArr['query']) && strpos($urlArr['query'], 'utm_source=yandexmarket') !== false) {
					$show = 0;
				}*/

				if (waRequest::cookie('wait_order_id', 0, 'int') > 0) {
					$show = 0;
				}
			}

			if ($show) {
				if ($summInCarts > 0) {
					$cart = new shopCart();
					$countInCarts = $cart->count();
					$totalInCarts = $cart->total();
					
					if (!$countInCarts OR $totalInCarts < $summInCarts) {
						$show = 0;
					}
				}
			}

			if ($show) {
				wa()->getResponse()->setCookie('wait_show', time(), time() + 3600 * 24 * $t0_cookie, '/');
			}
			
			$this->response = array(
				'result' => array(
					'is' => 'success',
					'show' => $show,
				)
			);
		}
    }

}