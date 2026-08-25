<?php

class shopLeadsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $u = $this->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        /** @var shopLeadsPlugin $plugin */
        $plugin = wa('shop')->getPlugin('leads');

        if (waRequest::post()) {
            $post = waRequest::post('leads', array(), waRequest::TYPE_ARRAY);
            $settings = array(
                'log_kp'             => !empty($post['log_kp']) ? 1 : 0,
                'log_zayavka'        => !empty($post['log_zayavka']) ? 1 : 0,
                'log_404'            => !empty($post['log_404']) ? 1 : 0,
                'log_wait'           => !empty($post['log_wait']) ? 1 : 0,
                'store_payload'      => !empty($post['store_payload']) ? 1 : 0,
                'show_badge'         => !empty($post['show_badge']) ? 1 : 0,
                'duplicate_minutes'  => max(0, (int) ifset($post, 'duplicate_minutes', 10)),
                'retention_months'   => max(0, (int) ifset($post, 'retention_months', 24)),
            );
            $plugin->saveSettings($settings);
            $this->view->assign('saved', true);
        }

        $this->getResponse()->setTitle('Заявки — настройки');
        $this->getResponse()->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $this->getResponse()->addHeader('Pragma', 'no-cache');

        $layout = new shopBackendLayout();
        $layout->assign('no_level2', true);
        $this->setLayout($layout);

        $this->view->assign(array(
            'settings'   => $plugin->getSettings(),
            'plugin_url' => wa()->getAppStaticUrl('shop') . 'plugins/leads/',
        ));
    }
}
