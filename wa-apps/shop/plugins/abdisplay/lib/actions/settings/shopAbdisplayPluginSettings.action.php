<?php

class shopAbdisplayPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $u = $this->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        /** @var shopAbdisplayPlugin $plugin */
        $plugin = wa('shop')->getPlugin('abdisplay');

        if (waRequest::post()) {
            $post = waRequest::post('experiments', array(), waRequest::TYPE_ARRAY);
            $experiments = shopAbdisplayHelper::getExperiments();
            foreach ($experiments as $key => $ex) {
                $row = ifset($post, $key, array());
                if (!is_array($row)) {
                    $row = array();
                }
                $experiments[$key]['enabled'] = !empty($row['enabled']) ? 1 : 0;
                $experiments[$key]['abtest_id'] = (int) ifset($row, 'abtest_id', 0);
                if (isset($row['label']) && trim((string) $row['label']) !== '') {
                    $experiments[$key]['label'] = trim((string) $row['label']);
                }
            }
            shopAbdisplayHelper::saveExperiments($experiments);
            $this->view->assign('saved', true);
        }

        $experiments = shopAbdisplayHelper::getExperiments();

        $abtest_model = new shopAbtestModel();
        $tests = $abtest_model->getTests();

        $summaries = array();
        $since = date('Y-m-d H:i:s', strtotime('-30 days'));
        try {
            $event_model = new shopAbdisplayPluginEventModel();
            foreach ($experiments as $key => $ex) {
                $summaries[$key] = $event_model->summarize($key, $since);
            }
        } catch (Exception $e) {
            // table may not exist yet
        }

        $this->getResponse()->setTitle('A/B отображение — настройки');
        $this->getResponse()->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        if (!waRequest::isXMLHttpRequest()) {
            $layout = new shopBackendLayout();
            $layout->assign('no_level2', true);
            $this->setLayout($layout);
        }

        $this->view->assign(array(
            'plugin'       => $plugin,
            'plugin_url'   => $plugin->getPluginStaticUrl(),
            'experiments'  => $experiments,
            'abtests'      => $tests,
            'summaries'    => $summaries,
            'since'        => $since,
            'can_force'    => shopAbdisplayHelper::canForce(),
        ));
    }
}
