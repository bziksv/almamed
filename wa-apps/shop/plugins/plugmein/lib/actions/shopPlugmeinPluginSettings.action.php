<?php

class shopPlugmeinPluginSettingsAction extends waViewAction
{
    private function getList()
    {
        $path = wa()->getAppPath('plugins', 'shop');
        $list = waFiles::listdir($path);
        foreach ($list as $key => $l) {
            if (!(is_dir($path.'/'.$l)&&file_exists($path.'/'.$l.'/lib/config/plugin.php'))) {
                unset($list[$key]);
            }
        }
        return ($list);
    }
    
    private function getOffInfo($id)
    {
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $plugin_config = $app_config->getPluginPath($id). '/lib/config/plugin.php';
        if (!file_exists($plugin_config)) {
            return false;
        }
        $plugin_info = include($plugin_config);
        foreach (array('name', 'description') as $field) {
            if (!empty($plugin_info[$field])) {
                $plugin_info[$field]=_wd('shop_'.$id, $plugin_info[$field]);
            }
        }
        return $plugin_info;
    }

    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getConfigPath('plugins.php', true);
        $plugin_php = include $path;
        $plugin_all = $this->getList();
        $unlisted = array_diff_key(array_flip($plugin_all), $plugin_php);
        if (count($unlisted)>0) {
            $unlisted = array_combine(array_keys($unlisted), array_fill(0, count($unlisted), false));
            $plugin_php = array_merge($plugin_php, $unlisted);
        }
        //Cache for 1 hour
        $md5 = md5(json_encode($plugin_all).filemtime($path));
        $cache = wa('shop')->getCache();
        if (!$cache || !($cache instanceof waCache)) {
            $cache = new waCache(new waFileCacheAdapter(array()), 'shop_plugmein');
        }
        $handlers_raw = $cache->get('handlers_'.$md5);
        $plugin_info = $cache->get('info_'.$md5);
        if (empty($plugin_info) || empty($handlers_raw)) {
            $handlers_raw = array();
            foreach ($plugin_php as $key => $state) {
                if ($key!=='plugmein') {
                    $plugin_info[$key] = $this->getOffInfo($key);
                    $plugin_info[$key]['id'] = $key;
                    $plugin_info[$key]['active'] = $state;
                    if (!empty($plugin_info[$key]['handlers'])) {
                        $handlers[$key] = array_fill_keys(array_keys($plugin_info[$key]['handlers']), $key);
                        $handlers_raw = array_merge_recursive($handlers[$key], $handlers_raw);
                    }
                }
            }
            unset($plugin);
            ksort($handlers_raw);
            $cache->set('handlers_'.$md5, $handlers_raw, 3600);
            $cache->set('info_'.$md5, $plugin_info, 3600);
        }
        $this->view->assign('handlers', $handlers_raw);
        $this->view->assign('plugin_list', $plugin_info);
        //Check installer rights
        $installer = ($this->getUser()->getRights('installer', 'settings')&&wa()->appExists('installer'));
        $this->view->assign('installer', $installer);

        $this->addSettingsVars();
        $this->addEventsInfo();
        $plugin = wa('shop')->getPlugin('plugmein');
        if (!$plugin->getSettings('long_events')) {
            wa()->getResponse()->setCookie('event_log_execution', '0', 0);
        }
    }

    private function addSettingsVars()
    {
        $plugin_id = 'plugmein';
        $vars = array();

        /**
         * @var shopPlugin $plugin
         */
        $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
        $namespace = wa()->getApp().'_'.$plugin_id;

        $params = array();
        $params['id'] = $plugin_id;
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings_controls = $plugin->getControls($params);

        $vars['plugin_info'] = array(
            'name' => ''
        );
        $vars['plugin_id'] = $plugin_id;
        $vars['settings_controls'] = $settings_controls;

        $this->view->assign($vars);
    }

    private function addEventsInfo()
    {
        $log = @file_get_contents(waConfig::get('wa_path_log') . '/webasyst/waEventExecutionTime.all.log');
        if (!$log) {
            return;
        }

        $re = '/\'class\' => \'(?<class>\w*)\'.*?0 => \'(?<method>\w*).*?\'execution_time\' => (?<time>[0-9\.]*)/s';
        preg_match_all($re, $log, $matches, PREG_SET_ORDER, 0);

        $classes = $methods = [];
        foreach ($matches as $m) {
            $classes[$m['class']] = ifset($classes[$m['class']]) + $m['time'];
            $methods[$m['class'] . '::' . $m['method']] = ifset($methods[$m['class'] . '::' . $m['method']]) + $m['time'];
        }
        arsort($classes);
        arsort($methods);

        $this->view->assign('classes', $classes);
        $this->view->assign('methods', $methods);
    }

    public static function classToLink($class)
    {
        $link = '';
        $chunks = preg_split('/(?=[A-Z])/', $class);
        $app = array_shift($chunks);
        if (wa()->appExists($app)) {
            $link = "apps/$app/";
        }
        if (end($chunks) === 'Plugin') {
            $plugin = strtolower($chunks[0]);
            $link = "plugins/$app/$plugin/";
        }
        return $link;
    }
}
