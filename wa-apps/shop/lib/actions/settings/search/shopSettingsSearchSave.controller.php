<?php

class shopSettingsSearchSaveController extends waJsonController
{
    public function execute()
    {
        /**
         * @var shopConfig $config
         */
        $config = $this->getConfig();
        $plugin = shopUserlogPlugin::getInstance();
        $before = $plugin ? array(
            'search_smart'  => $config->getOption('search_smart'),
            'search_weights'=> $config->getOption('search_weights'),
            'search_ignore' => $config->getOption('search_ignore'),
            'search_by_part'=> $config->getOption('search_by_part'),
        ) : null;

        $settings = $config->getOption(null);

        if (waRequest::post('smart') !== null) {
            $settings['search_smart'] = waRequest::post('smart') ? 1 : 0;
        } else {
            $settings['search_weights'] = waRequest::post('weights');
            $settings['search_ignore'] = waRequest::post('ignore');
            $settings['search_by_part'] = waRequest::post('by_part', 0, 'int');
        }

        $config_file = $config->getConfigPath('config.php');
        waUtils::varExportToFile($settings, $config_file);

        if ($plugin && $before !== null) {
            $after = array(
                'search_smart'   => ifset($settings, 'search_smart'),
                'search_weights' => ifset($settings, 'search_weights'),
                'search_ignore'  => ifset($settings, 'search_ignore'),
                'search_by_part' => ifset($settings, 'search_by_part'),
            );
            $plugin->logSettingsChange('Поиск', $before, $after);
        }
    }
}