<?php

class shopQuickeditorPlugin extends shopPlugin
{
    private static $plugin_reference;

    public function __construct($info)
    {
        parent::__construct($info);
        if (!self::$plugin_reference) {
            self::$plugin_reference = &$this;
        }
    }

    /**
     * Getting a reference of this plugin.
     *
     * @return object
     */
    public static function getPluginReference()
    {
        if (!self::$plugin_reference) {
            self::$plugin_reference = wa('shop')->getPlugin('quickeditor');
        }

        return self::$plugin_reference;
    }

    /**
     * Get gefault plugin settings.
     *
     * @return array Default plugin settings
     */
    public static function getDefaultSettings()
    {
        return array(
            'enable_plugin' => false,
            'frontend_head_hook' => true,
            'location_on_product' => 'right',
            'always_edit_page' => true,
            'tab_window' => false,
            'show_description' => false,
            'category_link_location' => 'right',
            'page_link_location' => 'right',
            'quick_access_location' => 'right',
            'save_and_close' => false,
            'show_buttons_title' => true,
            'open_setting_tab' => '',
            'edit_category_settings'=>false
        );
    }

    /**
     * Getting links to the scripts.
     *
     * @return string HTML-code
     */
    public static function getScripts()
    {
        $quickeditor = self::getPluginReference();
        $settings = $quickeditor->getCurrentStorefrontSettings();
        if ($quickeditor->getRights() && $settings['enable_plugin']) {
            $strings_arr = self::getStrings();
            if ($settings['page_link_location']
                && $settings['page_link_location'] != 'hidden'
                && waRequest::param('action', 'default') == 'page'
            ) {
                $settings['page_id'] = waRequest::param('page_id', '0');
            }
            $head = "\n<!-- QuickEditor plugin -->\n";
            $head .= '<link href="'.$quickeditor->getPluginStaticUrl(false).'css/quickeditor.min.css" rel="stylesheet" type="text/css">';
            $head .= '<script src="'.$quickeditor->getPluginStaticUrl(false).'js/quickeditor.min.js"></script>';
            $head .= '<script>$(function(){$.QuickEditor.init('.json_encode($settings).','.json_encode($strings_arr).');});</script>';
            $head .= "\n<!-- QuickEditor plugin -->\n";

            return $head;
        }
    }

    public static function getStrings()
    {
        return array(
                'str_save_update' => _wp('Save and update'),
                'str_save_and_close' => _wp('Save and close'),
                'str_edit_page' => _wp('Edit page'),
                'str_quick_access' => _wp('Quick access'),
                'str_settings' => _wp('Settings'),
                'str_plugins' => _wp('Plugins'),
                'str_orders' => _wp('Qrders'),
                'str_products' => _wp('Products'),
                'str_customers' => _wp('Customers'),
                'str_reports' => _wp('Reports'),
                'str_storefronts' => _wp('Storefronts'),
            );
    }

    /**
     * Hook: frontend_head.
     *
     * @return string HTML-code
     */
    public function frontendHead()
    {
        $settings = $this->getCurrentStorefrontSettings();                        
        if ($this->getRights() && $settings['frontend_head_hook']) {            
            return self::getScripts();
        }
    }

    /**
     * Hook: frontend_product.
     *
     * @param object $product shopProduct
     *
     * @return array HTML-code
     */
    public function frontendProduct($product)
    {
        $settings = $this->getCurrentStorefrontSettings();
        if ($this->getRights() && $settings['enable_plugin'] && $settings['location_on_product']) {
            $location_on_product = $settings['location_on_product'];
            $output_arr = array();
            $view = wa()->getView();
            $view->assign('product_id', $product['id']);
            $view->assign('product_name', $product['name']);
            $view->assign('location', $location_on_product);
            if ($location_on_product == 'left' || $location_on_product == 'right') {
                $output_arr = array('block' => $view->fetch(
                $this->getPath().
                    'QuickeditorProductLink.html'
                ));
            } else {
                $output_arr = array($location_on_product => $this->show($product['id'], true, true));
            }

            return $output_arr;
        }
    }

    /**
     * Hook: frontend_category.
     *
     * @param array $category Data of category
     *
     * @return string HTML-code
     */
    public function frontendCategory($category)
    {
        $settings = $this->getCurrentStorefrontSettings();
        if ($this->getRights() && $settings['enable_plugin']
            && $settings['category_link_location']
            && $settings['category_link_location'] != 'hidden'
        ) {
            $view = wa()->getView();
            $view->assign('category_id', $category['id']);
            $view->assign('location', $settings['category_link_location']);
            $view->assign('category_name', $category['name']);

            return $view->fetch($this->getPath().'QuickeditorCategoryLink.html');
        }
    }

    /**
     * Displaying the plugin.
     *
     * @param int  $product_id      Prouct ID
     * @param bool $is_product_page Flag for switch between display and fetch
     *
     * @return string HTML-code
     */
    public static function show($product_id = 0, $is_product_page = false, $is_hook = false)
    {
        $quickeditor = self::getPluginReference();
        $settings = $quickeditor->getCurrentStorefrontSettings();
        if ($quickeditor->getRights() && $settings['enable_plugin']) {
            if (!$is_hook) {
                waLocale::loadByDomain(array('shop', 'quickeditor'));
                waSystem::pushActivePlugin('quickeditor', 'shop');
            }
            $view = wa()->getView();
            $view->assign('product_id', (int) $product_id);
            $view->assign('is_product_page', $is_product_page);
            $view->assign('settings', $settings);
            if ($is_product_page || $is_hook) {
                return $view->fetch($quickeditor->getPath().'QuickeditorDefault.html');
            } else {
                $view->display($quickeditor->getPath().'QuickeditorDefault.html');
            }
            if (!$is_hook) {
                waSystem::popActivePlugin();
            }
        }
    }

    /**
     * Full path to plugin.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path.DIRECTORY_SEPARATOR.'templates'.
                DIRECTORY_SEPARATOR.'actions'.
                DIRECTORY_SEPARATOR.'quickeditor'.DIRECTORY_SEPARATOR;
    }

    public function getDefaultMainSettings()
    {
        return array(
            'enable_multisettings' => false,
            'active_storefront' => 'main',
        );
    }

    /**
     * Getting all storefront domains.
     *
     * @return array
     */
    public static function getStorefronts()
    {
        $storefronts = array();
        foreach (wa()->getRouting()->getByApp('shop') as $domain => $domain_routes) {
            $storefronts[] = $domain;
        }

        return array_unique($storefronts);
    }

    /**
     * Get active storefront settings.
     *
     * @return array
     */
    public function getCurrentStorefrontSettings()
    {        
        $main_settings = $this->getSettings('main');
        if (!is_array($main_settings)) {
            $main_settings = $this->getDefaultMainSettings();
        }
        if (!$main_settings['enable_multisettings']) {
            $storefront_settings = array_merge($this->getDefaultSettings(), $main_settings);
        } else {
            $current_domain = wa()->getConfig()->getDomain();
            $storefront_settings = $this->getSettings($current_domain);
            if (!is_array($storefront_settings)) {
                $storefront_settings = $this->getDefaultSettings();
            }
        }

        $storefront_settings['plugin_static_url'] = $this->getPluginStaticUrl(false);
        $storefront_settings['wa_backend_url'] = wa_backend_url();
        
   
        
        return $this->forceToBool($storefront_settings);
    }

    private function forceToBool($settings = array())
    {
        $default_settings = $this->getDefaultSettings();
        foreach ($default_settings as $key => $value) {
            if (isset($settings[$key]) && is_bool($value)) {
                $settings[$key] = (bool) $settings[$key];
            }
        }

        return $settings;
    }
    
    public static function getTabNames()
    {
        return array(
            'descriptions/' => _wp('Description & SEO'),
            'images/' => _wp('Images & Video'),
            'features/' => _wp('Features'),
            'services/' => _wp('Services'),
            'related/' => _wp('Related products'),
        );
    }
    
}
