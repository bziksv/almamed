<?php

class shopProductmanagerPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $plugin = wa('shop')->getPlugin('productmanager');
        $service = new shopProductmanagerService();

        $namespace = wa()->getApp() . '_productmanager';
        $params = array(
            'id' => 'productmanager',
            'namespace' => $namespace,
            'title_wrapper' => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper' => '<div class="name">%s</div><div class="value">%s %s</div>',
        );

        $categories = $service->getCategoryRows(false);
        $manager_pool_ids = $service->getManagerPoolIds();
        $managers = $service->getManagers();
        foreach ($managers as $id => &$manager) {
            $manager['in_pool'] = $manager_pool_ids === null || in_array($id, $manager_pool_ids, true);
        }
        unset($manager);
        $summary = $service->getSummary();

        $group_names = $service->getManagerGroupNames();
        $group_ids = $service->getManagerGroupIds();
        $resolved_group_names = array();
        if ($group_ids) {
            $resolved_group_names = array_values((new waGroupModel())->getName($group_ids));
        }

        $this->view->assign(array(
            'plugin_id' => 'productmanager',
            'plugin_url' => wa()->getAppUrl('shop') . '?plugin=productmanager',
            'app_url' => wa()->getAppUrl('shop'),
            'plugin_version' => $plugin->getVersion(),
            'managers' => $managers,
            'summary' => $summary,
            'categories' => $categories,
            'manager_group_names' => $group_names,
            'manager_group_resolved' => $resolved_group_names,
            'manager_group_missing' => !$group_ids,
            'manager_pool_ids' => $manager_pool_ids,
            'manager_pool_json' => json_encode($manager_pool_ids === null ? array() : $manager_pool_ids),
            'managers_json' => json_encode($managers, JSON_UNESCAPED_UNICODE),
            'categories_json' => json_encode($categories, JSON_UNESCAPED_UNICODE),
            'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE),
            'settings_controls' => $plugin->getControls($params),
            'asset_version' => waSystemConfig::isDebug() ? time() : $plugin->getVersion(),
        ));
    }
}
