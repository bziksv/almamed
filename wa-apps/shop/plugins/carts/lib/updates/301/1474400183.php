<?php
$plugin = array('shop', 'carts');

$asm = new waAppSettingsModel();
$settings = $asm->get($plugin);


if(
    empty($settings['cron_alert']) &&
    empty($settings['no_messages_alert']) &&
    empty($settings['contacts_save']) &&
    empty($settings['auth_save']) &&
    empty($settings['send_if_empty']) &&
    empty($settings['heartbeat']) &&
    empty($settings['delete_backend_order'])
) {
    $asm->set($plugin, 'contacts_save', 1);
    $asm->set($plugin, 'heartbeat', 1);
}