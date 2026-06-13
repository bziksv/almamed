#!/usr/bin/env php
<?php
/**
 * Post-deploy check: приложение userlog и плагины shop/blog.
 * Запуск на сервере из корня сайта:
 *   php .local/verify-userlog.php
 */
$root = dirname(__DIR__);
require $root.'/wa-system/autoload/waAutoload.class.php';
require $root.'/wa-config/SystemConfig.class.php';

$fail = 0;
$ok = function ($msg) { echo "  OK  {$msg}\n"; };
$bad = function ($msg) use (&$fail) { echo "  FAIL {$msg}\n"; $fail++; };

echo "=== userlog deploy check ===\n\n";

$app_path = $root.'/wa-apps/userlog/lib/config/app.php';
if (is_file($app_path)) {
    $ok('wa-apps/userlog/ на месте');
} else {
    $bad('нет wa-apps/userlog/ — сделайте git pull');
}

$apps_file = $root.'/wa-config/apps.php';
if (!is_file($apps_file)) {
    $bad('нет wa-config/apps.php');
} else {
    $apps = include $apps_file;
    if (!empty($apps['userlog'])) {
        $ok('userlog включён в wa-config/apps.php');
    } else {
        $bad('userlog НЕ в wa-config/apps.php — Настройки → Приложения или добавьте вручную: \'userlog\' => true');
    }
}

foreach (array(
    'shop' => $root.'/wa-config/apps/shop/plugins.php',
    'blog' => $root.'/wa-config/apps/blog/plugins.php',
) as $app => $file) {
    if (!is_file($file)) {
        $bad("нет {$file}");
        continue;
    }
    $plugins = include $file;
    if (!empty($plugins['userlog'])) {
        $ok("плагин userlog включён в {$app}");
    } else {
        $bad("плагин userlog выключен в {$app} — Магазин → Плагины → plugmein или plugins.php");
    }
}

try {
    waSystem::getInstance(null, new SystemConfig());
    wa('userlog');
    $model = new waModel();
    $model->query('SELECT 1 FROM userlog_event LIMIT 1');
    $ok('таблица userlog_event существует');
} catch (Exception $e) {
    $bad('БД userlog: '.$e->getMessage().' — выполните: php cli.php userlog install');
}

foreach (array(16, 24, 48) as $size) {
    $icon = $root."/wa-apps/userlog/img/userlog{$size}.png";
    if (is_file($icon)) {
        $ok("иконка userlog{$size}.png");
    } else {
        $bad("нет {$icon}");
    }
}

echo "\n";
if ($fail) {
    echo "Итог: {$fail} проблем(ы). Иконка «Лог пользователей» в шапке Webasyst появится после исправления.\n";
    echo "URL приложения: /webasyst/userlog/\n";
    exit(1);
}

echo "Итог: всё на месте. Если иконки всё ещё нет — проверьте права пользователя: Команда → доступ к приложению «Лог пользователей».\n";
echo "Полный тест: php .local/test-userlog-full.php\n";
exit(0);
