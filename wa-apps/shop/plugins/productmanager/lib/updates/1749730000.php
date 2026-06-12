<?php

// Do not call wa()->getPlugin()->saveSettings() here — it re-instantiates the plugin
// and re-runs checkUpdates(), causing infinite recursion and a white page.
$model = new waAppSettingsModel();
$current = trim((string) $model->get(array('shop', 'productmanager'), 'manager_group'));

if ($current === '' || mb_strtolower($current, 'UTF-8') === mb_strtolower('Менеджеры', 'UTF-8')) {
    $model->set(array('shop', 'productmanager'), 'manager_group', 'Менеджеры по продажам');
}
