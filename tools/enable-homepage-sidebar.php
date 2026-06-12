<?php
/**
 * Включить sidebar + sidebar_home в theme.xml (shop).
 * Webasyst хранит настройки темы в theme.xml, не в wa_app_settings.
 *
 * Запуск на сервере:
 *   sudo -u almamed.su php tools/enable-homepage-sidebar.php
 */
require dirname(__DIR__) . '/wa-config/SystemConfig.class.php';

$config = new SystemConfig('cli');
waSystem::getInstance(null, $config);
wa()->setActive('shop');

$theme_id = 'osnovnaja_new_header_footer_form';
$theme = new waTheme($theme_id, 'shop');

$before = $theme->getSettings(true);
$settings = $before;
$settings['sidebar'] = '1';
$settings['sidebar_home'] = '1';

$theme['settings'] = $settings;
$theme->save();

$after = (new waTheme($theme_id, 'shop'))->getSettings(true);

echo "Theme: {$theme_id}\n";
echo "sidebar:      " . var_export(ifset($before, 'sidebar', null), true) . " -> " . var_export($after['sidebar'], true) . "\n";
echo "sidebar_home: " . var_export(ifset($before, 'sidebar_home', null), true) . " -> " . var_export($after['sidebar_home'], true) . "\n";
echo "Done.\n";
