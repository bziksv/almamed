<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopWholesalePlugin extends shopPlugin
{

    public static $templates = array(
        'wholesale_js' => array(
            'name' => 'wholesale.js',
            'tpl_path' => 'plugins/wholesale/js/',
            'tpl_name' => 'wholesale',
            'tpl_ext' => 'js',
            'public' => true
        ),
    );

    public function saveSettings($settings = array())
    {
        $route_hash = waRequest::post('route_hash');
        $route_settings = waRequest::post('route_settings');

        if ($routes = $this->getSettings('routes')) {
            $settings['routes'] = $routes;
        } else {
            $settings['routes'] = array();
        }
        $settings['routes'][$route_hash] = $route_settings;
        $settings['route_hash'] = $route_hash;
        parent::saveSettings($settings);


        $templates = waRequest::post('templates');
        foreach ($templates as $template_id => $template) {
            $s_template = self::$templates[$template_id];
            if (!empty($template['reset_tpl'])) {
                $tpl_full_path = $s_template['tpl_path'] . $route_hash . '.' . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                $template_path = wa()->getDataPath($tpl_full_path, $s_template['public'], 'shop', true);
                @unlink($template_path);
            } else {
                $tpl_full_path = $s_template['tpl_path'] . $route_hash . '.' . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                $template_path = wa()->getDataPath($tpl_full_path, $s_template['public'], 'shop', true);
                if (!file_exists($template_path)) {
                    $tpl_full_path = $s_template['tpl_path'] . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                    $template_path = wa()->getAppPath($tpl_full_path, 'shop');
                }
                $content = file_get_contents($template_path);
                if (!empty($template['template']) && strcmp(str_replace("\r", "", $template['template']), str_replace("\r", "", $content)) != 0) {
                    $tpl_full_path = $s_template['tpl_path'] . $route_hash . '.' . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                    $template_path = wa()->getDataPath($tpl_full_path, $s_template['public'], 'shop', true);
                    $f = fopen($template_path, 'w');
                    if (!$f) {
                        throw new waException('Не удаётся сохранить шаблон. Проверьте права на запись ' . $template_path);
                    }
                    fwrite($f, $template['template']);
                    fclose($f);
                }
            }
        }
    }

    public function backendProductSkuSettings($params)
    {
        if ($this->getSettings('status')) {
            $sku = $params['sku'];
            $view = wa()->getView();
            $view->assign('sku', $sku);
            $view->assign('sku_id', $params['sku_id']);
            $html = $view->fetch('plugins/wholesale/templates/actions/backend/BackendProductSkuSettings.html');
            return $html;
        }
    }

    public function backendProductEdit($product)
    {
        if ($this->getSettings('status')) {
            $view = wa()->getView();
            $view->assign('product', $product);
            $html = $view->fetch('plugins/wholesale/templates/actions/backend/BackendProductEdit.html');
            return array('basics' => $html);
        }
    }

    public function backendCategoryDialog($category)
    {
        if ($this->getSettings('status')) {
            $view = wa()->getView();
            $view->assign(array(
                'category' => $category,
                'currency' => wa('shop')->getConfig()->getCurrency(true),
            ));
            $template_path = wa()->getAppPath('plugins/wholesale/templates/actions/backend/BackendCategoryDialog.html', 'shop');
            $html = $view->fetch($template_path);
            return $html;
        }
    }

    public function categorySave($category)
    {
        if ($this->getSettings('status')) {
            $update = array();
            if (($wholesale_min_sum = waRequest::post('wholesale_min_sum', -1)) != -1) {
                $update['wholesale_min_sum'] = $wholesale_min_sum;
            }
            if (($wholesale_min_product_count = waRequest::post('wholesale_min_product_count', -1)) != -1) {
                $update['wholesale_min_product_count'] = $wholesale_min_product_count;
            }
            if ($update) {
                $category_model = new shopCategoryModel();
                $category_model->updateById($category['id'], $update);
            }
        }
    }

    public function frontendCheckout($param)
    {
        $plugin = wa()->getPlugin('wholesale');
        if (!$plugin->getSettings('status')) {
            return false;
        }
        $route_hash = null;
        if (shopWholesaleRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = null;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings();
        } elseif (shopWholesaleRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings(0);
        } else {
            return false;
        }

        $cart = new shopCart();
        $result = shopWholesale::checkOrder();
        if (!$result['result'] && $param['step'] != 'success' && $route_settings['redirect']) {
            $cart_url = wa()->getRouteUrl('shop/frontend/cart');
            wa()->getResponse()->redirect($cart_url);
        }

        $data = wa()->getStorage()->get('shop/checkout');
        $plugins = $route_settings['plugins'];
        if (!empty($data['shipping']['id'])) {
            $shipping_id = $data['shipping']['id'];
            if (!empty($plugins[$shipping_id])) {
                $cart = new shopCart();
                $def_currency = wa('shop')->getConfig()->getCurrency(true);
                $cur_currency = wa('shop')->getConfig()->getCurrency(false);
                $total = $cart->total(true);
                $total = shop_currency($total, $cur_currency, $def_currency, false);

                if ($total < $plugins[$shipping_id]) {
                    $steps = array_keys(wa()->getConfig()->getCheckoutSettings());
                    $current_step_key = array_search($param['step'], $steps);
                    $shipping_step_key = array_search('shipping', $steps);
                    if ($current_step_key > $shipping_step_key && $route_settings['redirect']) {
                        $shipping_url = wa()->getRouteUrl('shop/frontend/checkout', array('step' => 'shipping'));
                        wa()->getResponse()->redirect($shipping_url);
                    }
                }
            }
        }

        if ($param['step'] == 'shipping') {
            $wholesale_js_url = shopWholesaleRouteHelper::getRouteTemplateUrl('wholesale_js', $route_hash);
            $version = $this->getVersion();

            $shipping_submit_selector = isset($route_settings['shipping_submit_selector']) ? $route_settings['shipping_submit_selector'] : '';
            $url = wa()->getRouteUrl('shop/frontend/shipping', array('plugin' => 'wholesale'));
            if (class_exists('shopOnestepPlugin')) {
                $onestep_url = wa()->getRouteUrl('shop/frontend/onestep', true);
            } else {
                $onestep_url = '';
            }

            return <<<HTML
<script type="text/javascript">
    $(function () {
        if ($.wholesale == undefined) {
            $.getScript("{$wholesale_js_url}?v{$version}")
            .done(function( script, textStatus ) {
                $.wholesale.shipping.init({
                    onestep_url: '{$onestep_url}',
                    url: '{$url}',
                    shipping_submit_selector: '{$shipping_submit_selector}'
                });
            })
            .fail(function( jqxhr, settings, exception ) {
                alert('Ошибка загрузки {$wholesale_js_url}');
            });
        } else {
            if (!$.wholesale.shipping.inited) {
                $.wholesale.shipping.init({
                    onestep_url: '{$onestep_url}',
                    url: '{$url}',
                    shipping_submit_selector: '{$shipping_submit_selector}'
                });
            }
        }
    });
</script>
HTML;
        }
    }

    private function setQuantity($item_id, $quantity)
    {
        $cart = new shopCart();
        $cart->setQuantity($item_id, $quantity);
        $url = wa()->getConfig()->getCurrentUrl();
        wa()->getResponse()->redirect($url);
    }

    public function frontendCart()
    {
        if (!$this->getSettings('status')) {
            return false;
        }
        $route_hash = null;
        if (shopWholesaleRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = null;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings();
        } elseif (shopWholesaleRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings($route_hash);
        } else {
            return false;
        }

        if ($route_settings['product_count_setting'] && !shopWholesale::checkMinProductsCartCount($product_name, $min_product_count, $item) && $route_settings['auto_add_product_count_setting']) {
            if ($item) {
                $this->setQuantity($item['id'], $min_product_count);
            }
        }

        if ($route_settings['product_count_setting'] && !shopWholesale::checkMinSkusCartCount($product_name, $min_sku_count, $item) && $route_settings['auto_add_product_count_setting']) {
            if ($item) {
                $this->setQuantity($item['id'], $min_sku_count);
            }
        }
        if ($route_settings['product_multiplicity_setting'] && !shopWholesale::checkMultiplicityProductsCartCount($product_name, $multiplicity_product_count, $item) && $route_settings['auto_add_product_multiplicity_setting']) {
            if ($item) {
                $k = ceil($item['quantity'] / $multiplicity_product_count);
                $quantity = $k * $multiplicity_product_count;
                $this->setQuantity($item['id'], $quantity);
            }
        }
        if ($route_settings['product_multiplicity_setting'] && !shopWholesale::checkMultiplicitySkusCartCount($product_name, $multiplicity_sku_count, $item) && $route_settings['auto_add_product_multiplicity_setting']) {
            if ($item) {
                $k = ceil($item['quantity'] / $multiplicity_sku_count);
                $quantity = $k * $multiplicity_sku_count;
                $this->setQuantity($item['id'], $quantity);
            }
        }

        $wholesale_js_url = shopWholesaleRouteHelper::getRouteTemplateUrl('wholesale_js', $route_hash);
        $version = $this->getVersion();

        $init_data = array(
            'url' => wa()->getRouteUrl('shop/frontend/cart', array('plugin' => 'wholesale')),
            'checkout_selector' => isset($route_settings['checkout_selector']) ? $route_settings['checkout_selector'] : '',
            'cart_actions' => array('save/', 'add/', 'delete/')

        );

        $ss_version = explode('.', wa('shop')->getVersion());
        if ($ss_version[0] >= 8) {
            $init_data['order_calculate_url'] = wa()->getRouteUrl('shop/frontendOrder', array('action' => 'calculate'));
        }

        if (class_exists('shopOnestepPlugin')) {
            $init_data['onestep_url'] = wa()->getRouteUrl('shop/frontend/onestep');
        }

        $json = json_encode($init_data);
        return <<<HTML
<script type="text/javascript" src="{$wholesale_js_url}?v{$version}"></script> 
<script type="text/javascript">
    $(function () {
        $.wholesale.cart.init({$json});
    });
</script>
HTML;
    }

    public function frontendProduct($product)
    {
        if (!$this->getSettings('status')) {
            return false;
        }
        $route_hash = null;
        if (shopWholesaleRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = null;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings();
        } elseif (shopWholesaleRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings(0);
        } else {
            return false;
        }

        $wholesale_js_url = shopWholesaleRouteHelper::getRouteTemplateUrl('wholesale_js', $route_hash);
        $version = $this->getVersion();

        $product_cart_form_selector = isset($route_settings['product_cart_form_selector']) ? $route_settings['product_cart_form_selector'] : '';
        $product_add2cart_selector = isset($route_settings['product_add2cart_selector']) ? $route_settings['product_add2cart_selector'] : '';
        $product_message = isset($route_settings['product_message']) ? $route_settings['product_message'] : '';
        $url = wa()->getRouteUrl('shop/frontend/product', array('plugin' => 'wholesale'));
        $html = <<<HTML
<script type="text/javascript" src="{$wholesale_js_url}"?v{$version}></script>
<script type="text/javascript">
    $(function () {
        $.wholesale.product.init({
            url: '{$url}',
            product_cart_form_selector: '{$product_cart_form_selector}',
            product_add2cart_selector: '{$product_add2cart_selector}',
            product_message: {$product_message}
        });
    });
</script>
HTML;
        return array('cart' => $html);
    }

    //устаревшие методы
    public static function display()
    {
        return false;
    }

    public static function displayFrontendCart()
    {
        return false;
    }

    public static function displayFrontendProduct()
    {
        return false;
    }

}
