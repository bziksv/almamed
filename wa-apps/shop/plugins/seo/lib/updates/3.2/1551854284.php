<?php


$app_settings_model = new waAppSettingsModel();
$time = $app_settings_model->get(array('shop', 'seo'), 'update_lock');

if ($time && time() < $time + 60)
{
	throw new waException('Идёт обновление SEO-оптимизации.');
}

$app_settings_model->set(array('shop', 'seo'), 'update_lock', time());

$update = new shopSeoUpdate1551854284();
$update->update();