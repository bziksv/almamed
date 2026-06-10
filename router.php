<?php
// Router for local dev: php -S almamed.su:8080 router.php

$local_hosts = array('localhost:8080', 'almamed.su:8080', '127.0.0.1:8080');

if (in_array($_SERVER['HTTP_HOST'] ?? '', $local_hosts, true)) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
