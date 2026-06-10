<?php

/*
 * Class shopYossPlugin
 * Product ajax-search plugin
 * Dynamically displays a list of products, their brands and categories
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopYossPlugin extends shopPlugin {

    /** Handler for frontend_head event: return plugin content in frontend. */
    public function frontendHead() {   
        $html = '';
        $settings = $this->getSettings();

        if ($settings['status'] === 'on' && $settings['frontend_head_status'] === 'on') {
            foreach ($settings as $id => $setting) {
                if ($id != 'result_css') {
                    $settings[$id] = addslashes(htmlspecialchars($setting));
                }

                $settings['result_max_height'] = (int)$settings['result_max_height'] . 'px';
                
                if ($settings['result_width'] != 'auto') { 
                    $settings['result_width'] = (int)$settings['result_width'] . 'px';
                }
            }

            $view = wa()->getView();
            $view->assign('yoss_settings', $settings);
            $view->assign('search_url', wa()->getRouteUrl('shop/frontend/smartsearch'));
            $html = $view->fetch($this->path.'/templates/Frontend.html');
        }
        
        return $html;
    }


    /**
     * Frontend method that initiates plugin
     * @return string
     */
    static function display() {
        $html = '';
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(array('shop', 'yoss'));

        if ($settings['status'] === 'on' && $settings['frontend_head_status'] === 'off') {
            
            waLocale::loadByDomain(array('shop', 'yoss'));
            waSystem::pushActivePlugin('yoss', 'shop');

            foreach ($settings as $id => $setting) {
                if ($id != 'result_css') {
                    $settings[$id] = addslashes(htmlspecialchars($setting));
                }

                $settings['result_max_height'] = (int)$settings['result_max_height'] . 'px';
                
                if ($settings['result_width'] != 'auto') { 
                    $settings['result_width'] = (int)$settings['result_width'] . 'px';
                }
            }

            $view = wa()->getView();
            $view->assign('yoss_settings', $settings);
            $view->assign('search_url', wa()->getRouteUrl('shop/frontend/smartsearch'));
            $html = $view->fetch(realpath(dirname(__FILE__)."/../").'/templates/Frontend.html');

            waSystem::popActivePlugin();
        }   
        
        return $html;
    }

    /**
     * Generates the HTML code for the user control with ID settingNumberControl for number parametrs
     * @param string $name
     * @param array $params
     * @return string
     */
    static public function settingNumberControl($name, $params = array()) {

        $control = '';

        $control_name = htmlentities($name, ENT_QUOTES, 'utf-8');

        $control .= "<input id=\"{$params['id']}\" type=\"number\" name=\"{$control_name}\" ";
        $control .= self::addCustomParams(array('class', 'placeholder', 'value',), $params);
        $control .= self::addCustomParams(array('min', 'max', 'step',), $params['options']);
        $control .= ">";

        return $control;

    }

    /**
     * Generates the HTML parts of code for the params in user controls added by plugin
     * @param array $list
     * @param array $params
     * @return string
     */
    private static function addCustomParams($list, $params = array()) {
        $params_string = '';

        foreach ($list as $param => $target) {
            if (is_int($param)) {
                $param = $target;
            }
            if (isset($params[$param])) {
                $param_value = $params[$param];
                if (is_array($param_value)) {
                    if (isset($param_value['title'])) {
                        $param_value = $param_value['title'];
                    } else {
                        $param_value = implode(' ', $param_value);
                    }
                }
                if ($param_value !== false) {
                    $param_value = htmlentities((string)$param_value, ENT_QUOTES, 'utf-8');
                    if (in_array($param, array('autofocus'))) {                     
                        $params_string .= " {$target}";
                    } else {                        
                        $params_string .= " {$target}=\"{$param_value}\"";
                    }
                }
            }
        }

        return $params_string;
    }

}