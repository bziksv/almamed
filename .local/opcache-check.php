<?php
header('Content-Type: text/plain');
echo 'opcache_enabled=' . (ini_get('opcache.enable') ? '1' : '0') . "\n";
if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    echo 'opcache_ok=' . ($s && !empty($s['opcache_enabled']) ? '1' : '0') . "\n";
}
