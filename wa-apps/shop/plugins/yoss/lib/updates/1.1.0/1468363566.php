<?php
/**
 * @author Max Severin <makc.severin@gmail.com>
 */
$plugin_id = array('shop', 'yoss');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'frontend_head_status', 'on');